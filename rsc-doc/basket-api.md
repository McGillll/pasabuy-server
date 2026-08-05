# Basket API

Customer-only "Blue Basket": a persistent, schedule-agnostic saved list. One basket per customer, auto-created on first fetch. All routes require `auth:sanctum`, `abilities:customer`, `customer.approved`.

## `GET /customer/basket`

Fetch (or create) the current user's basket. Supports `If-None-Match` / `ETag` conditional requests.

Response `data`:
```json
{
    "uuid": "...",
    "items": [
        {
            "uuid": "...",
            "product_uuid": "...",
            "product_name": "Lotte Milkis Regular Can",
            "product_image_url": "https://.../storage/...",
            "price": "149.00",
            "quantity": 2,
            "subtotal": 298.00
        }
    ],
    "total_items": 1,
    "total_amount": 298.00
}
```

`total_items` is the number of distinct line items in the basket, not the sum of quantities.

## `POST /customer/basket/items`

Add a product to the basket. If the product is already in the basket, `quantity` is added to the existing line (upsert), not duplicated.

Request: `{ "product_uuid": "...", "quantity": 1 }`
Response: full `BasketResource`, `201`.

## `PATCH /customer/basket/items/{uuid}`

Set a basket item's quantity to an exact value.

Request: `{ "quantity": 3 }`
Response: full `BasketResource`.

## `DELETE /customer/basket/items/{uuid}`

Remove a single item from the basket.
Response: full `BasketResource`.

## `DELETE /customer/basket`

Clear all items from the basket. Used by the client after a "send basket to a pasabuy" checkout completes.
Response: full `BasketResource` (now empty).
