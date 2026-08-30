"""
AI Service - Face Detection & Recognition
Phase 3: Placeholder for future implementation
"""

from fastapi import FastAPI

app = FastAPI(
    title="Family Archive AI Service",
    description="Face detection and recognition service",
    version="0.1.0",
)


@app.get("/health")
async def health():
    return {"status": "healthy", "service": "ai-service", "phase": "3"}


@app.post("/detect-faces")
async def detect_faces():
    """Phase 3: Detect faces in an image."""
    return {"message": "Not implemented yet - Phase 3"}


@app.post("/recognize-faces")
async def recognize_faces():
    """Phase 3: Recognize faces by comparing embeddings."""
    return {"message": "Not implemented yet - Phase 3"}


@app.post("/generate-embeddings")
async def generate_embeddings():
    """Phase 3: Generate face embeddings."""
    return {"message": "Not implemented yet - Phase 3"}
