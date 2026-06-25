---
title: WooCommerce node common issues
description: Documentation for common issues and questions in the WooCommerce node in n8n, a workflow automation platform. Includes details of the issue and suggested solutions.
contentType: [integration, reference]
priority: medium
---

# WooCommerce node common issues

Here are some common errors and issues with the [WooCommerce node](/integrations/builtin/app-nodes/n8n-nodes-base.woocommerce/index.md) and steps to resolve or troubleshoot them.

## The resource you are requesting could not be found

This error occurs when the WooCommerce node tries to fetch a specific resource by ID, but no resource with that ID exists in your WooCommerce store. The full error looks like this:

```
The resource you are requesting could not be found
```

Common causes include:

- **Hardcoded ID no longer exists**: The node is configured with a specific product, order, or customer ID that has since been deleted or never existed in the store.
- **Wrong store or environment**: The credential is pointing to a different WooCommerce store (for example, a staging site) where the resource doesn't exist.
- **Health check using Get instead of Get All**: Workflows that use the WooCommerce node to verify connectivity often use a **Get** operation with a hardcoded ID. If that resource doesn't exist, the node fails.

### Resolution

**For health check workflows**, replace the **Get** operation with a **Get All** operation and set the **Limit** to `1`. This validates that the WooCommerce API is reachable and responding without relying on a specific resource existing:

1. Open the **Check WooCommerce** node.
2. Change the **Operation** from **Get** to **Get All** (for example, **Get all products** or **Get all orders**).
3. Enable **Return All** or set **Limit** to `1`.

**For workflows fetching a specific resource**, verify that the resource ID exists in your WooCommerce store before passing it to the node. You can use an **If** node or **Error Trigger** to handle cases where the resource may not exist.

**If the error is unexpected**, confirm that:

- The **WooCommerce URL** in your credential matches the store you intend to query.
- WooCommerce permalinks are not set to **Plain** (go to **WordPress > Settings > Permalinks** and choose any option other than **Plain**).
- The WooCommerce REST API is enabled (go to **WooCommerce > Settings > Advanced > REST API**).
