#!/usr/bin/env python3
"""Prueba HTTP real del frontend PHP. Requiere MySQL aislado y servidor PHP activo.

No se conecta a la BD del usuario. Cada petición cruza el router, el controlador
y la sesión; las reservas se verifican desde dos clientes HTTP independientes.
"""
from __future__ import annotations
import base64
import datetime as dt
import http.cookiejar
from html.parser import HTMLParser
import json
import os
from pathlib import Path
import sys
import urllib.error
import urllib.parse
import urllib.request

BASE=os.getenv("TEST_BASE_URL","http://127.0.0.1:18080").rstrip("/")
ADMIN_EMAIL=os.getenv("GYM_INITIAL_ADMIN_EMAIL","")
ADMIN_PASSWORD=os.getenv("GYM_INITIAL_ADMIN_PASSWORD","")
REPORT=Path("reports/semana11_http.json")
checks=[]

class TokenParser(HTMLParser):
    def __init__(self):
        super().__init__()
        self.csrf=None
    def handle_starttag(self,tag,attrs):
        if tag.lower() != "input": return
        a=dict(attrs)
        if a.get("name")=="csrf" and self.csrf is None:
            self.csrf=a.get("value")

def client():
    jar=http.cookiejar.CookieJar()
    return urllib.request.build_opener(
        urllib.request.HTTPCookieProcessor(jar),
        urllib.request.HTTPRedirectHandler()
    )

def request(opener,route,method="GET",data=None,headers=None):
    headers=headers or {}
    body=None
    if data is not None:
        if headers.get("Content-Type")=="application/json":
            body=json.dumps(data).encode("utf-8")
        else:
            body=urllib.parse.urlencode(data).encode("utf-8")
            headers={**headers,"Content-Type":"application/x-www-form-urlencoded"}
    req=urllib.request.Request(BASE+route,data=body,method=method,headers=headers)
    try:
        with opener.open(req,timeout=18) as resp:
            return resp.status,resp.url,resp.read().decode("utf-8","replace")
    except urllib.error.HTTPError as e:
        return e.code,e.url,e.read().decode("utf-8","replace")

def check(name,condition,detail=""):
    checks.append({"prueba":name,"resultado":"OK" if condition else "FALLÓ","detalle":detail})
    print(("OK " if condition else "FALLÓ ")+name)
    if not condition:
        raise AssertionError(name+" "+detail)

def getcsrf(html):
    parser=TokenParser()
    parser.feed(html)
    if not parser.csrf: raise RuntimeError("No se encontró el token del formulario")
    return parser.csrf

def require_page(opener,route,term,name):
    status,url,body=request(opener,route)
    check(name,status==200 and term in body,f"HTTP {status}; destino {url}; contenido {body[:130]!r}")
    return body

def main():
    if not ADMIN_EMAIL or not ADMIN_PASSWORD:
        raise RuntimeError("Credenciales CI no configuradas.")
    visitante=client()
    socio=client()
    admin=client()
    fecha=(dt.datetime.now(dt.timezone.utc).date()+dt.timedelta(days=30)).isoformat()

    require_page(visitante,"/portal/index","Mallqui Gym","Portada accesible")
    status,_,body=request(visitante,"/cierre/index")
    check("Cierre administrativo protegido",status==200 and "Iniciar sesión" not in body and "Login -" in body)
    html=require_page(socio,"/portal/registrar","Crear cuenta de socio","Formulario de registro")
    csrf=getcsrf(html)
    status,url,body=request(socio,"/portal/guardarRegistro","POST",{
        "csrf":csrf,"nombre":"Socio Prueba Semana 11","dni":"98765432",
        "email":"socio-semana11@example.test","telefono":"987654321",
        "password":"ClaveSeguraSocio2026!"
    })
    check("Registro HTTP crea cuenta y retorna al login",
          status==200 and "portal/login" in url and "registrado" in url,
          f"HTTP {status}, destino {url}")
    html=require_page(socio,"/portal/login","Iniciar sesión","Login de socios")
    csrf=getcsrf(html)
    status,url,body=request(socio,"/portal/autenticar","POST",{
        "csrf":csrf,"email":"socio-semana11@example.test",
        "password":"equivocada"
    })
    check("Credenciales equivocadas rechazadas",
          status==200 and "credenciales" in url and "portal/login" in url,
          f"HTTP {status}; {url}")
    html=require_page(socio,"/portal/login","Iniciar sesión","Login sigue disponible")
    status,url,body=request(socio,"/portal/autenticar","POST",{
        "csrf":getcsrf(html),"email":"socio-semana11@example.test",
        "password":"ClaveSeguraSocio2026!"
    })
    check("Sesión del socio inicia correctamente",
          status==200 and "/portal/index" in url and "Mis reservas" in body,
          f"HTTP {status}; {url}")
    csrf=getcsrf(body)
    status,url,body=request(socio,"/portal/reservar","POST",{
        "csrf":csrf,"fecha":fecha,"hora":"12:00","tipo":"sesion"
    })
    check("Reserva desde portal persistida",
          status==200 and "reservada" in url and "12:00" in body,
          f"HTTP {status}; {url}")

    html=require_page(admin,"/auth/index","Sistema de Administración","Login administrador")
    status,url,body=request(admin,"/auth/login","POST",{
        "csrf":getcsrf(html),"email":ADMIN_EMAIL,
        "password":ADMIN_PASSWORD
    })
    check("Administrador inicia sesión",
          status==200 and "/home/index" in url and "Acceso Rápido" in body,
          f"HTTP {status}; {url}; HTML {body[:100]!r}")

    require_page(admin,"/reservas/index","Socio Prueba Semana 11","Dashboard ve reserva del cliente")
    paginas=[
        ("/socios/index","Socios","CRUD de socios"),
        ("/inventario/productos","Productos","Inventario de productos"),
        ("/pagos/index","Pagos de planes","Pagos"),
        ("/asistencia/index","Asistencia","Asistencia"),
        ("/rutinas/index","Rutinas por nivel","Rutinas"),
        ("/entrenador/index","Entrenador principal","Entrenador"),
        ("/reportes/index","Reportes","Reportes"),
        ("/usuarios/index","Usuarios","Administración de usuarios"),
        ("/revision/index","Verificación del proyecto EFSRT","Verificación EFSRT"),
        ("/cierre/index","Semana 11","Cierre de semana 11")
    ]
    for ruta,term,nombre in paginas:
        require_page(admin,ruta,term,nombre)

    status,url,report=request(admin,"/cierre/exportar")
    diagnostic=json.loads(report)
    check("Diagnóstico JSON exportable",
          status==200 and "conteos" in diagnostic
          and "datos" in diagnostic and "componentes" in diagnostic
          and diagnostic.get("tipo")=="diagnostico_tecnico_local",
          f"HTTP {status}")
    Path("reports").mkdir(exist_ok=True)
    Path("reports/semana11_diagnostico.json").write_text(
        json.dumps(diagnostic,ensure_ascii=False,indent=2),encoding="utf-8"
    )

    status,_,body=request(visitante,"/api/salud")
    check("API pública responde",status==200 and json.loads(body).get("ok") is True)
    status,_,body=request(visitante,"/api/login","POST",{
        "email":"socio-semana11@example.test",
        "password":"ClaveSeguraSocio2026!"
    },{"Content-Type":"application/json"})
    token=json.loads(body).get("token",{}).get("access_token")
    check("API login entrega token",status==200 and token and len(token)==64)
    status,_,body=request(visitante,"/api/reservas",
        headers={"Authorization":"Bearer "+token})
    api_result=json.loads(body)
    check("API y dashboard leen la misma reserva",
          status==200 and api_result.get("ok") is True
          and any(r.get("fecha")==fecha for r in api_result.get("reservas",[])))

    status,_,body=request(admin,"/auth/logout")
    status,url,body=request(admin,"/cierre/index")
    check("Cerrar sesión revoca acceso a cierre",status==200 and "/auth/index" in url)

    print(f"\nSEMANA 11: {len(checks)} comprobaciones HTTP correctas.\n")

if __name__=="__main__":
    failed=False
    error=None
    try:
        main()
    except Exception as exc:
        failed=True
        error=f"{type(exc).__name__}: {exc}"
        print(error,file=sys.stderr)
    finally:
        REPORT.parent.mkdir(parents=True,exist_ok=True)
        REPORT.write_text(json.dumps({
            "fecha_utc":dt.datetime.now(dt.timezone.utc).isoformat(),
            "entorno":"GitHub Actions - base MySQL temporal gym_system_ci",
            "resultado":"fallo" if failed else "correcto",
            "observacion":"Pruebas HTTP reales en servidor local CI, no en equipo del alumno",
            "error":error,
            "pruebas":checks,
        },ensure_ascii=False,indent=2),encoding="utf-8")
    sys.exit(1 if failed else 0)
