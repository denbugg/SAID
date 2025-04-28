# init_demands.py

from sqlalchemy import create_engine, Column, Integer, Date, MetaData, Table
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import sessionmaker
import datetime

# Путь к правильной базе данных
DATABASE_URL = "sqlite:///../said_done.db"

engine = create_engine(DATABASE_URL, connect_args={"check_same_thread": False})
metadata = MetaData()

# Полный список категорий
categories = [
    "flour", "meat_cans", "fish_cans", "sweet_cans", "vegetable_cans",
    "groats", "sugar", "pasta", "confectionery", "fastfood",
    "tea", "oil", "soap", "shampoo", "wipes",
    "toothpaste", "toothbrush", "toilet_paper", "female_hygiene", "razors"
]

# Пересоздание таблицы demands
def recreate_demands():
    metadata.reflect(bind=engine)
    if "demands" in metadata.tables:
        demands_table = metadata.tables["demands"]
        demands_table.drop(engine)
        metadata.clear()

    columns = [
        Column("id", Integer, primary_key=True),
        Column("date", Date, unique=True)
    ] + [Column(category, Integer, default=0) for category in categories]

    demands_table = Table("demands", metadata, *columns)
    metadata.create_all(engine)

# Заполнение таблицы demands тестовыми данными
def fill_demands():
    Session = sessionmaker(bind=engine)
    session = Session()

    today = datetime.date.today()
    demands_table = metadata.tables["demands"]

    for i in range(30):
        date = today + datetime.timedelta(days=i)
        values = {category: 1000 for category in categories}
        values.update({"date": date})
        ins = demands_table.insert().values(**values)
        engine.execute(ins)

    session.commit()
    session.close()

if __name__ == "__main__":
    recreate_demands()
    fill_demands()
    print("Таблица demands переинициализирована и заполнена!")
