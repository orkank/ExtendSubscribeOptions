# GraphQL Examples for ExtendSubscribeOptions Module

## Query: Get Subscription Options Configuration

This query retrieves all available subscription options with their configuration including labels, subtitles, and descriptions.

```graphql
query {
  subscriptionOptionsConfig {
    email {
      enabled
      label
      subtitle
      description
    }
    call {
      enabled
      label
      subtitle
      description
    }
    sms {
      enabled
      label
      subtitle
      description
    }
    whatsapp {
      enabled
      label
      subtitle
      description
    }
  }
}
```

### Example Response:

```json
{
  "data": {
    "subscriptionOptionsConfig": {
      "email": {
        "enabled": true,
        "label": "E-posta Bülteni",
        "subtitle": "Kampanyalar ve yeni ürünler hakkında bilgi alın",
        "description": "E-posta ile size özel fırsatlar ve yeni ürün duyuruları göndereceğiz."
      },
      "call": {
        "enabled": true,
        "label": "Telefon ile Arama",
        "subtitle": "Özel teklifler için sizi arayabiliriz",
        "description": "Sadece özel kampanyalar için size ulaşacağız."
      },
      "sms": {
        "enabled": true,
        "label": "SMS Bildirimleri",
        "subtitle": "Kısa mesaj ile bildirim alın",
        "description": "Sipariş durumu ve özel fırsatlar hakkında SMS göndereceğiz."
      },
      "whatsapp": {
        "enabled": true,
        "label": "WhatsApp İletişimi",
        "subtitle": "WhatsApp üzerinden iletişim",
        "description": "WhatsApp ile size özel fırsatlar ve sipariş güncellemeleri göndereceğiz."
      }
    }
  }
}
```

## Query: Get Customer Subscription Status (Requires Authentication)

This query retrieves the current customer's subscription preferences.

```graphql
query {
  customerSubscriptionStatus {
    customer_id
    email
    allow_call
    allow_sms
    allow_whatsapp
  }
}
```

### Example Response:

```json
{
  "data": {
    "customerSubscriptionStatus": {
      "customer_id": 12345,
      "email": "customer@example.com",
      "allow_call": true,
      "allow_sms": false,
      "allow_whatsapp": true
    }
  }
}
```

## Mutation: Update Customer Subscription Options (Requires Authentication)

This mutation updates the customer's subscription preferences.

```graphql
mutation {
  updateCustomerSubscriptionOptions(
    input: {
      allow_call: true
      allow_sms: false
      allow_whatsapp: true
    }
  ) {
    customer {
      email
      allow_call
      allow_sms
      allow_whatsapp
    }
  }
}
```

### Example Response:

```json
{
  "data": {
    "updateCustomerSubscriptionOptions": {
      "customer": {
        "email": "customer@example.com",
        "allow_call": true,
        "allow_sms": false,
        "allow_whatsapp": true
      }
    }
  }
}
```

## Mutation: Create Customer with Subscription Preferences

When creating a new customer, you can include subscription preferences:

```graphql
mutation {
  createCustomer(
    input: {
      firstname: "John"
      lastname: "Doe"
      email: "john.doe@example.com"
      password: "SecurePassword123!"
      allow_call: true
      allow_sms: true
      allow_whatsapp: false
    }
  ) {
    customer {
      id
      email
      firstname
      lastname
      allow_call
      allow_sms
      allow_whatsapp
    }
  }
}
```

## Mutation: Update Customer with Subscription Preferences

When updating an existing customer, you can modify subscription preferences:

```graphql
mutation {
  updateCustomer(
    input: {
      firstname: "John"
      lastname: "Doe"
      allow_call: false
      allow_sms: true
      allow_whatsapp: true
    }
  ) {
    customer {
      id
      email
      firstname
      lastname
      allow_call
      allow_sms
      allow_whatsapp
    }
  }
}
```

## Testing with cURL

### Get Subscription Options Config (No Authentication Required)

```bash
curl -X POST https://your-domain.com/graphql \
  -H "Content-Type: application/json" \
  -H "Store: default" \
  -d '{
    "query": "query { subscriptionOptionsConfig { email { enabled label subtitle description } call { enabled label subtitle description } sms { enabled label subtitle description } whatsapp { enabled label subtitle description } } }"
  }'
```

### Get Customer Subscription Status (Requires Authentication)

```bash
curl -X POST https://your-domain.com/graphql \
  -H "Content-Type: application/json" \
  -H "Store: default" \
  -H "Authorization: Bearer YOUR_CUSTOMER_TOKEN" \
  -d '{
    "query": "query { customerSubscriptionStatus { customer_id email allow_call allow_sms allow_whatsapp } }"
  }'
```

### Update Customer Subscription Options (Requires Authentication)

```bash
curl -X POST https://your-domain.com/graphql \
  -H "Content-Type: application/json" \
  -H "Store: default" \
  -H "Authorization: Bearer YOUR_CUSTOMER_TOKEN" \
  -d '{
    "query": "mutation { updateCustomerSubscriptionOptions(input: { allow_call: true, allow_sms: false, allow_whatsapp: true }) { customer { email allow_call allow_sms allow_whatsapp } } }"
  }'
```

## Notes

1. **Authentication**: Queries and mutations that require authentication need a valid customer token in the `Authorization` header.
2. **Store Header**: Include the `Store` header to specify which store view to use.
3. **Field Selection**: You can select only the fields you need in your queries to optimize response size.
4. **Error Handling**: Always check for errors in the GraphQL response.

## Configuration

All subscription options are configured in:
**Stores > Configuration > IDangerous > Subscription Options**

Each option (email, call, sms, whatsapp) has:
- **Enable**: Turn the option on/off
- **Label**: Display text for the checkbox
- **Subtitle**: Additional descriptive text shown below the label
- **Description**: Detailed information shown in a modal/popup

