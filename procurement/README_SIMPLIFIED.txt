SIMPLIFIED PROCUREMENT MODULE
=====================================

The procurement module has been streamlined to focus on 4 core features:

1. DASHBOARD
   - Overview with statistics
   - Quick stats on low stock items, pending orders, and total inventory
   - Quick action buttons to navigate to main features

2. LOW STOCK ITEMS (low_stock.php)
   - View all inventory items below their low stock threshold
   - Statistics showing:
     * Total items below threshold
     * Out of stock items
     * Critical stock items
   - Search functionality
   - One-click purchase order creation from low stock items
   - Color-coded stock levels (red bar indicator)

3. PURCHASE ORDERS (purchase_orders.php)
   - View all purchase orders with statuses
   - Filter by status: Pending, Approved, Ordered, Received
   - Update order status easily
   - Delete orders if needed
   - Statistics dashboard showing count by status
   - Statuses:
     * Pending - Initial order state
     * Approved - Order approved for procurement
     * Ordered - Order placed with supplier
     * Received - Stock received

4. NOTIFY STAFF (notify_staff.php)
   - Create notifications for inventory staff
   - Alert staff when new stock arrives
   - Track notification status (sent/pending)
   - Filter notifications by status
   - Send notifications to staff
   - Delete notifications if needed
   - Statistics showing sent and pending notifications

SIDEBAR NAVIGATION
==================
The sidebar now only shows these 4 sections:
- Dashboard
- Low Stock Items
- Purchase Orders
- Notify Staff
- Logout

REMOVED FEATURES
================
- Create Purchase Request page
- Purchase Request List page
- Suppliers page
- Goods Received page
- Inventory management (moved to procurement dashboard)
- Reports page
- Upload Logs page
- Batch management (old system)

DATABASE TABLES
===============
Created two new tables:
1. purchase_orders - Stores all purchase orders
2. notifications - Stores notifications for staff

BACKEND FILES
=============
- create_order.php - Creates new purchase order
- update_order.php - Updates order status
- delete_order.php - Deletes order
- create_notification.php - Creates staff notification
- send_notification.php - Marks notification as sent
- delete_notification.php - Deletes notification

All features are clean, simple, and focused on the core procurement workflow.
