# 🏠 Smart Home Controller System

## ✅ Complete File Structure

```
htdocs/smart_home/
├── index.php                  (Login page)
├── signup.php                 (Registration page)
├── dashboard_admin.php        (Admin dashboard)
├── dashboard_user.php         (User dashboard)
├── dashboard_provider.php     (Provider dashboard)
├── scheduler.php              (Background scheduler - AJAX polling)
├── logout.php                 (Logout handler)
├── db.php                     (Database connection)
├── auth.php                   (Session authentication)
├── style.css                  (Global stylesheet)
├── script.js                  (JavaScript functions)
└── README.md                  (This file)
```

---

## 🚀 Installation Steps

### Step 1: Database Setup

1. Open **phpMyAdmin** (<http://localhost/phpmyadmin>)
2. Import your SQL file (`smart_home.sql`)
3. Database `smart_home` will be created with all tables

### Step 2: File Setup

1. Copy all files to `C:\xampp\htdocs\smart_home\`
2. Ensure all 11 files are in the same folder (no subfolders)

### Step 3: Start XAMPP

1. Start **Apache** server
2. Start **MySQL** server

### Step 4: Access Application

Visit: **<http://localhost/smart_home/>**

---

## 👥 Default Login Credentials

| Role     | Email                  | Password  |
|----------|------------------------|-----------|
| Admin    | <admin@smarthome.com>    | (check your database) |
| User     | <john@user.com>          | (check your database) |
| Provider | <philips@provider.com>   | (check your database) |

**Note:** You set passwords as plain text, so check the `users` table for actual passwords.

---

## 🎯 System Features

### 🔐 Authentication

- ✅ Login/Signup system
- ✅ Role-based access (admin/user/provider)
- ✅ Session timeout (30 minutes)
- ✅ Auto-redirect to role-specific dashboard

### 👨‍💼 Admin Features

- ✅ View all users
- ✅ Delete users (except admins)
- ✅ View all devices
- ✅ Assign devices to users
- ✅ Remove device assignments
- ✅ View all activity logs

### 👤 User Features

- ✅ View assigned devices
- ✅ Toggle devices ON/OFF
- ✅ Schedule device actions
- ✅ View personal activity logs
- ✅ Delete pending schedules

### 🏭 Provider Features

- ✅ Register new devices
- ✅ Update device compatibility versions
- ✅ Post firmware/service updates
- ✅ View all posted updates

### ⏰ Scheduler Features

- ✅ Runs every 30 seconds via AJAX polling
- ✅ Executes schedules within next 30 seconds
- ✅ Auto-updates device status
- ✅ Logs all scheduler actions
- ✅ Shows toast notifications on execution

---

## 🔧 Configuration

### Database Connection (`db.php`)

```php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');           // Your MySQL password
define('DB_NAME', 'smart_home');
```

### Session Timeout (`auth.php`)

```php
$timeout = 1800; // 30 minutes (in seconds)
```

---

## 📊 Database Tables

1. **users** - User accounts (admin, user, provider)
2. **devices** - Smart home devices
3. **device_assignments** - User-device mappings
4. **schedules** - Scheduled device actions
5. **activity_logs** - All system activities
6. **providers** - Provider companies
7. **updates** - Device firmware/service updates

---

## 🎨 UI Features

✅ Modern gradient design (purple theme)
✅ Responsive cards layout
✅ Toast notifications (animated)
✅ Tab-based navigation
✅ Mobile responsive
✅ Clean table views
✅ Real-time status updates

---

## ⚡ How Scheduler Works

1. **User Dashboard** starts AJAX polling when page loads
2. Every **30 seconds**, browser sends request to `scheduler.php`
3. Scheduler checks for schedules where:
   - `status = 'pending'`
   - `scheduled_time` is between NOW and +30 seconds
4. Executes matching schedules:
   - Updates device status
   - Marks schedule as 'executed'
   - Logs activity
5. User dashboard auto-refreshes data
6. Toast notification shows execution

---

## 🧪 Testing Guide

### Test 1: User Login & Device Control

1. Login as `john@user.com`
2. Go to "My Devices" tab
3. Toggle device ON/OFF
4. Check "My Activity" for logs

### Test 2: Scheduling

1. As user, go to "Schedules" tab
2. Select device, action, and time (set 1 minute from now)
3. Click "Schedule Action"
4. Wait 30-60 seconds
5. Check device status updates automatically
6. Toast notification appears

### Test 3: Admin Assignment

1. Login as admin
2. Go to "Assignments" tab
3. Select user and device
4. Click "Assign Device"
5. Login as that user to verify device appears

### Test 4: Provider Functions

1. Login as provider
2. Go to "My Devices"
3. Add new device
4. Update device version
5. Post update in "Updates" tab

---

## 🐛 Troubleshooting

### Issue: "Connection failed"

**Solution:** Check MySQL is running, verify credentials in `db.php`

### Issue: "Session expired" immediately

**Solution:** Check PHP session configuration, ensure cookies enabled

### Issue: Scheduler not running

**Solution:**

- Check browser console for errors
- Verify `scheduler.php` is accessible
- Make sure you're on user dashboard (scheduler only runs there)

### Issue: Device toggle not working

**Solution:**

- Check device is assigned to user
- Verify `device_assignments` table has correct entry

### Issue: Blank page

**Solution:**

- Enable PHP error reporting: Add to `db.php`:

  ```php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```

---

## 📝 Important Notes

⚠️ **Password Security:** Currently passwords are stored as plain text as per your requirement. For production, use `password_hash()`.

⚠️ **SQL Injection:** Basic escaping used. For production, use prepared statements.

⚠️ **Scheduler:** AJAX polling works for testing. For production, use cron job.

⚠️ **Session Security:** Sessions timeout after 30 minutes of inactivity.

---

## 🎓 Usage Tips

1. **Always test scheduler** by setting schedule 1-2 minutes ahead
2. **Check activity logs** to track all actions
3. **Assign devices before users can control them**
4. **Provider must register devices** before admin can assign them

---

## 📞 Support

If you encounter any issues:

1. Check browser console (F12) for JavaScript errors
2. Check PHP error logs in `xampp/apache/logs/error.log`
3. Verify database structure matches SQL dump
4. Ensure all 11 files are present in same directory

---

## ✨ Features Summary

✅ 3 Role-based dashboards
✅ Device control (ON/OFF)
✅ Scheduler with AJAX polling (30s)
✅ Activity logging
✅ Device assignment system
✅ Provider device management
✅ Session timeout (30 min)
✅ Toast notifications
✅ Responsive design
✅ Modern UI with gradients

---

**Built with:** PHP, MySQL, CSS, JavaScript
**No dependencies:** Pure vanilla code, works offline in XAMPP
**Database:** MariaDB 10.4.32
**Browser:** Chrome, Firefox, Edge (modern browsers)
