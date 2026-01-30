# Webhook Orchestrator

[![CI](https://github.com/adriacanal/webhook-orchestrator/actions/workflows/ci.yml/badge.svg?branch=develop)](https://github.com/adriacanal/webhook-orchestrator/actions/workflows/ci.yml?query=branch%3Adevelop)

Webhook orchestration service built with Laravel.  
It ingests third-party webhooks, enforces idempotency, normalizes events and processes them asynchronously.  
Designed with Docker (Laravel Sail), GitHub Actions CI and an AWS-ready architecture.

---

## Motivation

In real-world systems, multiple external providers (payments, e-commerce, forms, etc.) send webhooks with different payloads, retry strategies and delivery guarantees.

This service centralizes webhook ingestion to:
- Guarantee idempotent processing
- Decouple ingestion from processing
- Normalize heterogeneous payloads
- Provide observability and traceability per event

---

## Features (current)

- Webhook ingestion endpoint (`POST /api/webhooks/stripe`)
- Persistent storage of raw webhook payloads
- Idempotency based on provider + provider event ID
- Feature tests for ingestion and duplicate handling
- CI pipeline with GitHub Actions

---

## Architecture (high level)

- Laravel API application
- MySQL for persistence
- Dockerized local development via Laravel Sail
- CI via GitHub Actions
- Designed to integrate with AWS services (SQS, RDS, ECS)

The system follows an **ingest → persist → process** flow, where webhook ingestion is kept fast and idempotent, and heavier processing is delegated to asynchronous jobs.

---

## Local development

### Requirements
- Docker

### Setup
```bash
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
```

## Roadmap

Planned improvements:

- Webhook signature verification (HMAC)
- Asynchronous processing via queue workers
- Event normalization layer
- Routing rules and delivery tracking
- AWS integration (SQS, RDS, ECS)
- Deployment pipeline (CD)
