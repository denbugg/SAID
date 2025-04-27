# routers/auth.py

from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from backend.schemas import schemas
from backend.models import models
from backend.db import SessionLocal

router = APIRouter(
    prefix="/api/auth",
)

def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

@router.post("/login", response_model=schemas.UserOut)
def login(user: schemas.UserLogin, db: Session = Depends(get_db)):
    db_user = db.query(models.User).filter(models.User.phone == user.phone).first()
    if not db_user:
        raise HTTPException(status_code=400, detail="Пользователь не найден")
    if db_user.hashed_password != user.password:
        raise HTTPException(status_code=400, detail="Неверный пароль")
    return db_user
