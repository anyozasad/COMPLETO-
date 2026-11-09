// Evidencias de pantallas reales con Chromium. Solo base aislada de GitHub Actions.
import fs from 'node:fs/promises';
import { chromium } from 'playwright';

const base=(process.env.TEST_BASE_URL || 'http://127.0.0.1:18080').replace(/\/$/,'');
const reportDir='reports';
await fs.mkdir(reportDir,{recursive:true});
const login=process.env.GYM_INITIAL_ADMIN_EMAIL;
const clave=process.env.GYM_INITIAL_ADMIN_PASSWORD;
if(process.env.CI!=='true' || process.env.DB_NAME!=='gym_system_ci' || !login || !clave){
  throw new Error('Capturas solo permitidas en entorno CI aislado');
}
const errors=[];
const screens=[];
const externos=[];
const imagenesRotas=[];
const browser=await chromium.launch({headless:true});
try {
  const adminContext=await browser.newContext({viewport:{width:1440,height:900},deviceScaleFactor:1});
  await adminContext.route('**/*',route=>{
    const url=route.request().url();
    // Bloquear el acceso de la app a todo CDN externo, sin bloquear Apache local.
    if(/^https?:/i.test(url) && !url.startsWith(base+'/')){
      externos.push(url);
      return route.abort();
    }
    return route.continue();
  });
  const page=await adminContext.newPage();
  page.on('pageerror',err=>errors.push({pagina:page.url(),error:err.message}));

  async function shot(slug,url,term){
    const resp=await page.goto(base+url,{waitUntil:'load'});
    if(!resp || !resp.ok()){
      throw new Error('Pantalla HTTP incorrecta '+slug+' '+resp?.status());
    }
    if(term && !(await page.locator('body').innerText()).includes(term)){
      throw new Error('Pantalla sin texto esperado: '+slug+' / '+term);
    }
    const rotas=await page.locator('img').evaluateAll(imgs=>
      imgs.filter(x=>x.getClientRects().length>0 && x.complete &&
        x.naturalWidth===0).map(x=>x.currentSrc||x.src));
    for(const imagen of rotas) imagenesRotas.push({pantalla:slug,imagen});
    await page.screenshot({path:reportDir+'/semana11_'+slug+'.png',fullPage:true,animations:'disabled'});
    screens.push({pantalla:slug,ruta:url,resultado:'visible',codigo:resp.status()});
    console.log('CAPTURA '+slug+' OK');
  }
  await shot('portal','/portal/index','Mallqui Gym');
  await shot('registro','/portal/registrar','Crear cuenta de socio');
  await shot('login_admin','/auth/index','Sistema de Administración');

  await page.locator('input[name="email"]').fill(login);
  await page.locator('input[name="password"]').fill(clave);
  await page.locator('button[type="submit"]').click();
  await page.waitForURL('**/home/index',{timeout:15000});

  await shot('dashboard','/home/index','Acceso Rápido');
  await shot('reservas','/reservas/index','Reservas');
  await shot('inventario','/inventario/productos','Productos');
  await shot('cierre','/cierre/index','Semana 11');
  await shot('reporte','/revision/index','Verificación del proyecto EFSRT');

  const mobile=await adminContext.newPage({viewport:{width:390,height:844}});
  mobile.on('pageerror',err=>errors.push({pagina:mobile.url(),error:err.message}));
  await mobile.goto(base+'/portal/index',{waitUntil:'load'});
  await mobile.screenshot({path:reportDir+'/semana11_portal_movil.png',fullPage:true,animations:'disabled'});
  screens.push({pantalla:'portal_movil',ruta:'/portal/index',resultado:'visible',viewport:'390x844'});

  await fs.writeFile(reportDir+'/semana11_navegador.json',
    JSON.stringify({
      entorno:'GitHub Actions, MySQL temporal, Chromium',
      advertencia:'Capturas técnicas de datos de prueba. No representan instalación del alumno.',
      fecha:new Date().toISOString(),pantallas:screens,
      errores_js:errors,peticiones_externas_bloqueadas:externos,
      imagenes_rotas:imagenesRotas
    },null,2),'utf8');
  if(errors.length){
    throw new Error('Chromium reportó '+errors.length+' errores JavaScript. Revisar semana11_navegador.json');
  }
  if(externos.length){
    throw new Error('Hay '+externos.length+' peticiones CDN externas. Revisar semana11_navegador.json');
  }
  if(imagenesRotas.length){
    console.error('IMAGENES ROTAS:',JSON.stringify(imagenesRotas));
    throw new Error('Hay '+imagenesRotas.length+' imágenes rotas. Revisar semana11_navegador.json');
  }
  console.log('PRUEBAS NAVEGADOR OK: '+screens.length+' capturas.');
} finally {
  await browser.close();
}
