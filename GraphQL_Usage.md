# GraphQL Usage for ExtendSubscribeOptions Module

This module extends Magento 2's GraphQL API to support querying and updating customer subscription preferences for Call, SMS, and WhatsApp notifications.

## Query Customer Subscription Attributes

You can now include the subscription attributes in your customer queries:

```graphql
query Customer {
    customer {
        id
        firstname
        lastname
        email
        is_subscribed
        allow_call
        allow_sms
        allow_whatsapp
        created_at
        date_of_birth
        default_billing
        default_shipping
        dob
        gender
        group_id
        middlename
        phone_number
        phone_verified
        prefix
        suffix
        taxvat
    }
}
```

## Update Customer Subscription Options

### Method 1: Using the Standard updateCustomer Mutation

You can now include subscription attributes in the standard `updateCustomer` mutation:

```graphql
mutation UpdateCustomer {
    updateCustomer(input: {
        firstname: "John"
        lastname: "Doe"
        allow_call: true
        allow_sms: false
        allow_whatsapp: true
    }) {
        customer {
            id
            firstname
            lastname
            allow_call
            allow_sms
            allow_whatsapp
        }
    }
}
```

**Note**: Use boolean values (`true`/`false`) instead of integers (`1`/`0`) in GraphQL mutations.

### Method 2: Using the Dedicated Subscription Options Mutation

For updating only subscription preferences:

```graphql
mutation UpdateCustomerSubscriptionOptions {
    updateCustomerSubscriptionOptions(input: {
        allow_call: true
        allow_sms: false
        allow_whatsapp: true
    }) {
        customer {
            id
            firstname
            lastname
            allow_call
            allow_sms
            allow_whatsapp
        }
    }
}
```

## Authentication

All GraphQL operations require customer authentication. Make sure to include the customer authorization token in your request headers:

```
Authorization: Bearer <customer_token>
```

## Response Format

The subscription attributes return boolean values:
- `true`: Customer allows this type of communication
- `false`: Customer does not allow this type of communication
- `null`: Not set (defaults to false)

## Error Handling

The GraphQL resolvers include comprehensive error handling and logging. Common errors:

- **GraphQlAuthorizationException**: Customer not logged in or invalid token
- **GraphQlInputException**: Invalid input data or missing required fields
- **General Exception**: Database or attribute access issues (logged for debugging)

## Logging

All GraphQL operations are logged for debugging purposes:
- Query operations: Attribute access attempts and errors
- Mutation operations: Update attempts with input data and customer ID

Check Magento logs for troubleshooting: `var/log/system.log`

## Get Subscription Options Configuration

You can query the configuration details for the subscription options to know which checkboxes are enabled and get their labels and descriptions:

```graphql
query SubscriptionOptionsConfig {
    subscriptionOptionsConfig {
        call {
            enabled
            label
            description
        }
        sms {
            enabled
            label
            description
        }
        whatsapp {
            enabled
            label
            description
        }
    }
}
```

### Response Format

```json
{
    "data": {
        "subscriptionOptionsConfig": {
            "call": {
                "enabled": true,
                "label": "Allow phone calls",
                "description": "We may contact you via phone for important updates"
            },
            "sms": {
                "enabled": true,
                "label": "Allow SMS messages",
                "description": "Receive SMS notifications about your orders"
            },
            "whatsapp": {
                "enabled": false,
                "label": "WhatsApp",
                "description": ""
            }
        }
    }
}
```

### Configuration Details

- **enabled**: Boolean indicating if the option is enabled in admin configuration
- **label**: Display text for the checkbox (configured in admin or defaults to option name)
- **description**: Descriptive text explaining the option (configured in admin)
- **Store-aware**: Configuration is retrieved based on the current store scope

This query does not require authentication and can be used to dynamically show/hide subscription options in your frontend based on admin configuration.

## Get Current Customer's Subscription Status

You can query the current logged customer's subscription preferences. This query requires authentication:

```graphql
query CustomerSubscriptionStatus {
    customerSubscriptionStatus {
        customer_id
        email
        allow_call
        allow_sms
        allow_whatsapp
    }
}
```

### Response Format (Authenticated)

```json
{
    "data": {
        "customerSubscriptionStatus": {
            "customer_id": 183056,
            "email": "customer@example.com",
            "allow_call": true,
            "allow_sms": false,
            "allow_whatsapp": true
        }
    }
}
```

### Response Format (Not Authenticated)

```json
{
    "errors": [
        {
            "message": "The current customer isn't authorized.",
            "extensions": {
                "category": "graphql-authorization"
            }
        }
    ],
    "data": {
        "customerSubscriptionStatus": null
    }
}
```

### Authentication Required

This query requires a valid customer authorization token in the request headers:

```
Authorization: Bearer <customer_token>
```

### Features

- **Authentication Check**: Returns authorization error if customer is not logged in
- **Current Values**: Returns the actual subscription preferences stored for the customer
- **Customer Info**: Includes customer ID and email for verification
- **Boolean Values**: All subscription flags are returned as true/false
- **Error Handling**: Graceful error handling with proper GraphQL error responses