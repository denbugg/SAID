# backend/main.py

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from fastapi.staticfiles import StaticFiles
from fastapi.responses import RedirectResponse, FileResponse
import os

from db import engine
from models import models
from routers import auth, calendar, orders, products

# Создание всех таблиц базы данных
models.Base.metadata.create_all(bind=engine)

# Инициализация приложения
app = FastAPI(
    title="SAID-DONE Backend",
    description="API и сайт для проекта SAID-DONE",
    version="1.0"
)

# Разрешаем CORS для всех (для фронта)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Подключение роутеров API
app.include_router(auth.router, tags=["Авторизация"])
app.include_router(calendar.router, tags=["Календарь"])
app.include_router(orders.router, tags=["Заказы"])
app.include_router(products.router, tags=["Товары"])

# Настройка статики
frontend_path = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "frontend"))
static_path = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "static"))

app.mount("/static", StaticFiles(directory=static_path), name="static")
app.mount("/frontend", StaticFiles(directory=frontend_path), name="frontend")

# 👉 Редиректы для всех страниц

@app.get("/")
async def root():
    return RedirectResponse(url="/frontend/login.html")

@app.get("/login.html")
async def login_page():
    return FileResponse(frontend_path + "/login.html")

@app.get("/calendar.html")
async def calendar_page():
    return FileResponse(frontend_path + "/calendar.html")

@app.get("/lk.html")
async def lk_page():
    return FileResponse(frontend_path + "/lk.html")

@app.get("/market.html")
async def market_page():
    return FileResponse(frontend_path + "/market.html")

@app.get("/warehouse_tasks.html")
async def warehouse_tasks_page():
    return FileResponse(frontend_path + "/warehouse_tasks.html")

@app.get("/warehouse_issue.html")
async def warehouse_issue_page():
    return FileResponse(frontend_path + "/warehouse_issue.html")

@app.get("/volunteer_delivery.html")
async def volunteer_delivery_page():
    return FileResponse(frontend_path + "/volunteer_delivery.html")

@app.get("/volunteer_issue.html")
async def volunteer_issue_page():
    return FileResponse(frontend_path + "/volunteer_issue.html")
