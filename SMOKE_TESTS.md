# Restoku API Smoke Tests

Manual API checks for core flows.

## Auth + OTP
```
POST /api/v1/auth/register
POST /api/v1/auth/login
POST /api/v1/auth/verify-otp
```
- Expect OTP flow to require verify before token use.
- Verify response has token and user role.

Example (register):
```
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Tester","email":"tester@example.com","password":"password123"}'
```

Example (login):
```
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"tester@example.com","password":"password123"}'
```

Example (verify OTP):
```
curl -X POST http://localhost:8000/api/v1/auth/verify-otp \
  -H "Content-Type: application/json" \
  -d '{"email":"tester@example.com","code":"123456"}'
```

## Orders (Idempotent)
1) Create order with Idempotency-Key.
2) Repeat same request with same Idempotency-Key -> expect same order response.
3) Repeat with same key + different payload -> expect 409.

Example (create order):
```
curl -X POST http://localhost:8000/api/v1/orders \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -H "Idempotency-Key: order-abc-123" \
  -d '{"restaurant_table_id":1,"items":[{"menu_id":1,"quantity":2}]}'
```

## Invalid Status Transition
1) Create order (pending).
2) Call `PATCH /api/v1/orders/{id}/status` with `done` directly.
3) Expect 409 "Transisi status tidak valid".

Example (invalid transition):
```
curl -X PATCH http://localhost:8000/api/v1/orders/1/status \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{"status":"done"}'
```

## Assign Staff (Staff/Kitchen)
```
curl -X PATCH http://localhost:8000/api/v1/orders/1/assign \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{"assigned_to":"Kitchen A"}'
```

## Notifications (SSE)
```
curl -N http://localhost:8000/api/v1/notifications/stream \
  -H "Authorization: Bearer <token>" \
  -H "Accept: text/event-stream"
```
- Expect periodic `ping` and `data:` messages when orders are done.

## Orders (Polling)
```
curl -G http://localhost:8000/api/v1/orders/poll \
  -H "Authorization: Bearer <token>" \
  --data-urlencode "status=done" \
  --data-urlencode "updated_after=2024-01-01T00:00:00Z" \
  --data-urlencode "per_page=20"
```
- Expect lightweight list with `id`, `restaurant_table_id`, `status`, `notified_at`, `updated_at`.
