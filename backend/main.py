# main.py

from faujnjdjstapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from backend.models import models
from backend.db import engine
from backend.routers import auth, calendar, orders, products

# Создание таблиц
models.Base.metadata.create_all(bind=engine)

app = FastAPI(
    title="SAID-DONE Backend",
    description="API для проекта гуманитарной помощи",
    version="1.0"
)

# Разрешаем запросы с любого источника
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Подключение маршрутов (роутеров)
app.include_router(auth.router, tags=["Авторизация"])
app.include_router(calendar.router, tags=["Календарь"])
app.include_router(orders.router, tags=["Заказы"])
app.include_router(products.router, tags=["Продукты"])
