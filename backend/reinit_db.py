# backend/reinit_demands.py

from sqlalchemy import create_engine, MetaData
from db import Base
from models import Demand
from db import SQLALCHEMY_DATABASE_URL

# Создание подключения к БД
engine = create_engine(SQLALCHEMY_DATABASE_URL)

# Удаление старой таблицы
metadata = MetaData()
metadata.reflect(bind=engine)
if 'demands' in metadata.tables:
    Demand.__table__.drop(engine)
    print("Старая таблица 'demands' удалена.")

# Создание новой таблицы
Base.metadata.create_all(bind=engine)
print("Новая таблица 'demands' успешно создана.")
