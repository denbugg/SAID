# routers/calendar.py

from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from backend.schemas import schemas
from backend.models import models
from backend.db import SessionLocal
import datetime

router = APIRouter(
    prefix="/api/calendar",
)

def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

@router.get("/get")
def get_calendar(date: str, db: Session = Depends(get_db)):
    date_obj = datetime.datetime.strptime(date, "%Y-%m-%d")
    demand = db.query(models.Demand).filter(
        models.Demand.date_start <= date_obj,
        models.Demand.is_active == True
    ).order_by(models.Demand.date_start.desc()).first()

    if not demand:
        raise HTTPException(status_code=404, detail="Потребность на эту дату не найдена")

    return {
        "humanitarian_quantity": demand.humanitarian_quantity,
        "hygiene_quantity": demand.hygiene_quantity
    }

@router.post("/create")
def create_demand(demand: schemas.DemandCreate, db: Session = Depends(get_db)):
    new_demand = models.Demand(
        humanitarian_quantity=demand.humanitarian_quantity,
        hygiene_quantity=demand.hygiene_quantity,
        is_active=True
    )
    db.add(new_demand)
    db.commit()
    db.refresh(new_demand)
    return new_demand
