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

## Stripe Webhooks

Stripe webhooks are ingested through a dedicated HTTP endpoint protected by a middleware that verifies the Stripe-Signature header using Stripe’s official SDK.

### Flow

1. Incoming webhook requests are validated by a middleware using Stripe\Webhook::constructEvent.
2. Invalid signatures or malformed payloads are rejected before reaching the application layer.
3. Valid events are processed by the webhook handler and stored idempotently.

### Testing

Webhook requests are tested using raw request bodies and real HMAC signatures to accurately reproduce Stripe’s signing mechanism.

---

## Local development

### Requirements
- Docker

### Ports (local)
If ports 80/3306/6379 are already taken on your host, configure:
- APP_PORT (default 80)
- FORWARD_DB_PORT (default 3306)
- FORWARD_REDIS_PORT (default 6379)

### Setup
```bash
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
```

## Roadmap

Planned improvements:

- [x] Webhook signature verification (HMAC)
- [x] Webhook ingestion
- [x] Idempotent processing for duplicate webhooks
- Asynchronous processing via queue workers
- Event normalization layer
- Routing rules and delivery tracking
- AWS integration (SQS, RDS, ECS)
- Deployment pipeline (CD)
