# Family Digital Archive System - Implementation Roadmap

## Project Overview

Build a private family digital archive platform that preserves physical photographs digitally while adding modern search, face recognition, and storytelling capabilities.

---

# Technology Stack

## Frontend
- Nuxt 3
- TypeScript
- Tailwind CSS
- Pinia
- VueUse

## Backend
- Laravel 12
- PHP 8.4+
- Laravel Sanctum
- Laravel Socialite
- Laravel Queue

## Database
- PostgreSQL

## Object Storage
- MinIO (S3-compatible)

## Cache & Queue
- Redis

## AI Service
- Python
- FastAPI
- InsightFace
- OpenCV

## OCR
- Tesseract OCR

## Deployment
- Docker Compose
- Nginx Reverse Proxy
- Cloudflare (optional)

---

# Phase 1 - Foundation MVP

## Goal

Provide a secure family-only platform where approved users can upload and browse photographs.

## Functional Requirements

### Authentication

#### Google Login
- User signs in using Google OAuth.
- First login creates user record.
- New users remain in Pending status.

#### Admin Approval
- Admin receives notification.
- Admin can:
  - Approve
  - Reject
  - Suspend

#### Roles
- Admin
- Family Member

### User Management

#### User Table

Fields:
- id
- name
- email
- google_id
- status
- role
- created_at
- updated_at

### Photo Upload

Support:

- Front image
- Back image (optional)

Validation:

- JPG
- PNG
- WEBP
- Maximum size configurable

### Photo Metadata

Fields:

- Title
- Year
- Approximate date
- Location
- Description
- Uploaded by

### Photo Viewer

Features:

- Gallery view
- Grid view
- Fullscreen view
- Zoom support

### Flip Photo Feature

Requirements:

- Front side displayed first
- User clicks Flip
- CSS 3D transform reveals back image
- Works on desktop and mobile

### Audit Logging

Track:

- Login
- Upload
- Edit
- Delete

### Security

Requirements:

- HTTPS only
- Authenticated access only
- Signed URLs for images
- CSRF protection
- Rate limiting

---

## Database Design

### users

### photos

Fields:

- id
- title
- front_image_path
- back_image_path
- description
- taken_year
- taken_date
- location
- uploaded_by

### activity_logs

Fields:

- user_id
- action
- ip_address
- created_at

---

## Deliverables

- Login system
- Approval workflow
- Upload photos
- Flip photo functionality
- Gallery browsing
- Audit logging

---

# Phase 2 - People & Face Tagging

## Goal

Allow family members to identify people appearing in photographs.

## Functional Requirements

### Person Directory

Create people records independently from users.

Example:

People:
- Grandfather
- Grandmother
- Uncle Ali
- Baby Faiz

### Person Table

Fields:

- id
- name
- nickname
- birth_year
- death_year
- notes

### Face Detection

Upon upload:

1. Queue job created.
2. Image sent to AI service.
3. Faces detected.
4. Bounding boxes stored.

### Face Regions

Store:

- x
- y
- width
- height

### Face Review Interface

User sees:

Face #1
Face #2
Face #3

User can:

- Select existing person
- Create new person
- Mark unknown

### Photo-Person Relationships

Many-to-many relationship.

---

## Search

Search by:

- Person name
- Year
- Location
- Description

---

## Deliverables

- Person management
- Face detection
- Face tagging
- Search capability

---

# Phase 3 - Face Recognition

## Goal

Automatically identify known individuals.

## Functional Requirements

### Face Embeddings

For each tagged face:

- Generate embedding
- Store vector

### Recognition Workflow

Upload photo

→ Detect faces

→ Compare embeddings

→ Suggest identity

Example:

95% confidence:
Grandfather

User confirms or rejects.

### Confidence Thresholds

90%+
Auto Suggest

70–90%
Needs Review

Below 70%
Unknown

---

## AI Architecture

Laravel

→ Queue Job

→ Python Service

→ PostgreSQL

### Python Components

- FastAPI
- InsightFace
- OpenCV

Endpoints:

POST /detect-faces

POST /recognize-faces

POST /generate-embeddings

---

## Deliverables

- Automatic recognition
- Confidence scoring
- User confirmation workflow

---

# Phase 4 - OCR & Historical Preservation

## Goal

Digitize handwriting and notes on the back of photographs.

## OCR Workflow

Back image uploaded

→ OCR processing

→ Extract text

→ Store searchable content

### OCR Storage

photo_ocr_texts

Fields:

- photo_id
- extracted_text

### Search

User searches:

"Kuala Lumpur"

Results include:

- Description matches
- OCR matches

---

## Deliverables

- OCR extraction
- OCR search

---

# Phase 5 - Timeline & Storytelling

## Goal

Transform archive into a family history platform.

## Features

### Timeline View

Browse:

- 1960s
- 1970s
- 1980s
- 1990s

### Stories

Each photo can contain:

- Caption
- Story
- Historical notes

### Comments

Family members can:

- Discuss memories
- Correct details
- Add context

---

## Deliverables

- Timeline
- Stories
- Comments

---

# Phase 6 - Family Tree

## Goal

Connect people together.

### Relationships

- Parent
- Child
- Sibling
- Spouse

### Visualization

Interactive family tree.

### Integration

Open person profile

→ View photos

→ View relatives

---

## Deliverables

- Family tree
- Relationship management

---

# Phase 7 - Advanced Intelligence

## Duplicate Detection

Use perceptual hashing.

### Workflow

Upload image

→ Generate pHash

→ Compare existing photos

→ Suggest duplicates

## Smart Search

Examples:

"Photos of grandfather in the 1980s"

"Photos taken in Kuala Lumpur"

### Future Options

- Natural language search
- AI-generated descriptions
- Event clustering

---

# Infrastructure Requirements

## Docker Services

- nginx
- frontend
- backend
- postgres
- redis
- minio
- ai-service

## Backup Strategy

Daily:

- PostgreSQL dump
- MinIO backup

Retention:

- Daily: 30 days
- Monthly: 12 months

## Monitoring

Recommended:

- Uptime Kuma
- Grafana
- Prometheus

---

# Suggested Development Order

Sprint 1
- Authentication
- User approval

Sprint 2
- Upload photos
- Gallery

Sprint 3
- Flip functionality
- Metadata

Sprint 4
- Face detection

Sprint 5
- Person management

Sprint 6
- Face recognition

Sprint 7
- OCR

Sprint 8
- Timeline

Sprint 9
- Family tree

Sprint 10
- Advanced AI features

---

# Success Criteria

A successful system should:

- Preserve all family photographs digitally
- Preserve handwritten notes on photo backs
- Allow family-only access
- Automatically identify family members
- Enable historical storytelling
- Remain maintainable for 10+ years
- Be fully self-hosted and portable
