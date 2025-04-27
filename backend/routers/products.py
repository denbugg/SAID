# routers/products.py

from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from backend.schemas import schemas
from backend.models import models
from backend.db import SessionLocal

router = APIRouter(
    prefix="/api/products",
)

def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

@router.post("/intake")
def intake_products(products: list[schemas.ProductBase], db: Session = Depends(get_db)):
    for product in products:
        new_product = models.Product(
            uid=product.uid,
            product_type_id=product.product_type_id,
            status=models.ProductStatus.ON_WAREHOUSE
        )
        db.add(new_product)

    db.commit()
    return {"message": "Товары успешно приняты на склад"}

@router.get("/list")
def list_products(db: Session = Depends(get_db)):
    products = db.query(models.Product).all()
    return products
