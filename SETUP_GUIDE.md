# Setup Guide — Movie Database Management System

## Prerequisites
You will need to install two tools:
- **XAMPP** — provides Apache to serve the PHP files
- **MySQL Workbench** — provides a MySQL client to import the database schema

---

## Step 1: Install XAMPP

1. Download XAMPP from https://www.apachefriends.org/
2. When the UAC warning appears, click OK
3. **Important:** Change the install path from `C:\Program Files\xampp` to `C:\xampp` to avoid permission issues
4. Complete the installation with default settings

![](images/1.1.png)
![](images/1.2.png)
![](images/1.3.png)
![](images/1.4.png)
![](images/1.5.png)

---

## Step 2: Copy the Project Files

Open Command Prompt and run:

```cmd
xcopy /E /Y "path\to\db_movie" "C:\xampp\htdocs\db_movie\"
```

Replace `path\to\db_movie` with the actual folder location (e.g. `d:\111\db_movie`).

![](images/2.png)

---

## Step 3: Start Apache

1. Open the XAMPP Control Panel (`C:\xampp\xampp-control.exe`)
2. Click **Start** next to **Apache**
3. Apache must remain running whenever you use the app

![](images/3.1.png)
![](images/3.2.png)

---

## Step 4: Install MySQL Workbench

1. Download MySQL Workbench from https://dev.mysql.com/downloads/workbench/
2. Click "No thanks, just start my download" to skip account creation
3. During setup, choose **Client only**
4. Complete the installation; skip the MySQL Router Configuration screen by clicking **Finish**

![](images/4.1.png)
![](images/4.2.png)
![](images/4.3.png)
![](images/4.4.png)
![](images/4.5.png)

---

## Step 5: Import the Database Schema

1. Open **MySQL Workbench**
2. Click **+** next to "MySQL Connections" and fill in:
   - **Connection Name:** anything (e.g. `School`)
   - **Hostname:** your remote server IP
   - **Username:** your username
   - Click **Store in Vault** and enter your password
3. Click **OK**, then click the connection to open it
4. Click **File → Open SQL Script**, select `db_data.sql`, and open it
5. **Important:** Before running, update all three `TODO` lines in the SQL file:
   - `USE`: change to your **database name**
   - `CREATE TABLE IF NOT EXISTS`: change the table name to your **student ID** (e.g. `A123456789`)
   - `INSERT INTO`: change the table name to the same **student ID**
6. Click the **⚡ lightning bolt** (or `Ctrl+Shift+Enter`) to run it

This creates your table and inserts the seed data.

![](images/5.1.png)
![](images/5.2.png)
![](images/5.3.png)
![](images/5.4.png)
![](images/5.5.png)

---

## Step 6: Use the App

Open your browser and go to:

```
http://localhost/db_movie/
```

You can now:
- **View** all movies on the main page
- **Add** a new movie via the 新增資料 link
- **Edit** a movie via the 編輯 button
- **Delete** a movie via the 刪除 button

All changes are saved directly to the remote database.

![](images/6.png)

---

## Step 7: Verify Changes

To confirm changes were saved, either:
- **Refresh** `http://localhost/db_movie/` — changes should persist
- **Or** open MySQL Workbench, and run:
  ```sql
  SELECT * FROM table_name; -- remember to change to your own table name
  ```
  The results will show the current state of the database.

![](images/7.png)
