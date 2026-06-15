# Restaurant_Project / 餐廳顧客回饋管理系統

本專案是一個基於 **關聯式資料庫 (MySQL/MariaDB)** 與 **PHP** 開發的「餐廳顧客回饋管理系統」。提供直觀的網頁使用介面，並完整實作了資料庫新增 (Insert)、刪除 (Delete)、修改 (Modify)、多表連接查詢 (Query with JOIN)、資料庫交易防護 (Transaction) 以及進階複雜統計報表。

---

## 💻 使用的程式語言與技術 (Technologies & Languages)
* **後端 (Backend)**：`PHP` (採用 `mysqli` 模組連結資料庫，並使用 Prepared Statements 防範 SQL Injection)
* **前端 (Frontend)**：`HTML5`、`CSS3` (現代化卡片式響應佈局與 CSS 變數)、`JavaScript` (DOM 元素動態處理、動態下拉選單聯動篩選)
* **資料庫 (Database)**：`SQL` (MySQL / MariaDB DDL 結構、外鍵級聯、Transaction 交易防護、進階複雜查詢)
* **伺服器 (Server)**：Apache (XAMPP / AppServ 整合套件)

---

## 🛠️ 系統功能特色

### 1. 顧客回饋主儀表板 (`index.php`)
* **多表連接查詢 (JOIN)**：整合 `Feedback_forms`、`Customers`、`Consumption_records` 等多個 Tables，一目了然呈現顧客姓名、用餐桌號、整體評分與回饋意見。
* **即時模糊搜尋**：內建關鍵字搜尋框，支援搜尋顧客姓名、桌號或意見內容。

### 2. 動態多項顧客回饋新增 (`create.php`)
* **聯動動態篩選**：
  * 選擇顧客後，「消費紀錄」下拉選單僅會列出該名顧客的消費。
  * 選擇消費紀錄後，系統會自動快取該訂單，讓「餐點評價」下拉選單僅顯示該訂單實際點過的餐點，且「外場服務員」也僅顯示當時服務該桌的同仁。
* **動態多餐點評價**：支援顧客點擊 `➕ 增加餐點評價` 按鈕，自由評價一至多道餐點，並支援動態移除。
* **資料庫交易 (Transaction)**：後端以 ACID 交易安全機制包裹，確保主要回饋、弱實體細節與評分關聯表同時寫入成功，否則自動 Rollback 撤銷。

### 3. 回饋資料修改與級聯刪除 (`update.php`, `delete.php`)
* **動態編輯**：自動載入舊有回饋內容與多個已評價的餐點卡片，使用者可自由修改、增加或刪除細部評論，後端同樣以交易防護更新。
* **外鍵級聯刪除 (`ON DELETE CASCADE`)**：點擊刪除後，資料庫將利用外鍵級聯機制，在刪除主回饋表單的同時**自動清除**對應的弱實體明細與評分連結，確保資料庫一致性。

### 4. 進階統計報表與複雜查詢 (`advanced_query.php`)
* **聚合函數與群組 (`AVG`, `COUNT` + `JOIN`)**：
  * **餐點排行**：統計各餐點的平均滿意度得分與被評價次數（供內場廚師查看）。
  * **員工表現**：統計外場服務同仁的服務平均滿意度與獲評次數（供經理考評）。
* **集合比較 (Set Comparison - 嵌套子查詢)**：篩選出整體評分高於「全店所有回饋平均分」的優良回饋表單。
* **集合成員 (Set Membership - `IN` 子查詢)**：篩選出「曾給過 5 星滿分好評」的顧客基本資料，方便精準VIP行銷。

### 5. 組員協作整合功能
* **管理者首頁 (`manager_home.php`)**：系統管理者之主頁。
* **建立用餐紀錄 (`create_record.php`)**：新增與編輯用餐消費紀錄。
* **顧客總覽 (`customer_data.php`)**：管理顧客基本資料與會員等級。

---

## 📥 Git Clone 專案後如何使用與執行

本專案目前已預先串接至**遠端資料庫伺服器**。當您或他人 `clone` 專案後，預設不需要匯入 SQL 資料庫，只需開啟 Apache 網頁伺服器即可直接使用！

### 步驟一：複製並移動專案
1. 使用 Git 下載專案：
   ```bash
   git clone https://github.com/yor7815/Restaurant_Project.git
   ```
2. 將下載下來的 `Restaurant_Project` 資料夾，放到您的網頁伺服器根目錄（例如 XAMPP 的 **`C:\xampp\htdocs\`** 或 **`D:\xampp\htdocs\`**）。

### 步驟二：啟動 Apache 網頁伺服器
1. 開啟您的 **XAMPP Control Panel**。
2. 點擊 **Apache** 旁邊的 **Start** 按鈕（使其亮綠燈）。
3. *（注意：由於預設已串接遠端資料庫，您在此處**不需要**啟動本地的 MySQL）。*

### 步驟三：瀏覽網頁介面
開啟瀏覽器，直接前往以下網址，即可正常瀏覽與操作系統（數據會自動與遠端資料庫同步）：
👉 **[http://localhost/restaurant_project/](http://localhost/restaurant_project/)**

---

## 💡 如何切換為「本地端（Local）資料庫」開發？

如果您想要斷網開發，或者希望使用自己本機的資料庫：

1. **啟動本機 MySQL**：在 XAMPP Control Panel 中將 **MySQL** 按下 **Start** 啟動。
2. **匯入測資結構**：打開 MySQL Workbench 連上本地資料庫後，開啟並執行專案中的 [db_data.sql](db_data.sql) 腳本。
3. **更換連線設定**：開啟 [db_config.php](db_config.php)，將遠端伺服器資訊替換成您的本地資訊：
   ```php
   $servername = "127.0.0.1";
   $port = 3306;
   $username = "root";
   $password = ""; // 本地預設無密碼
   $dbname = "team14"; // 或是您匯入的資料庫名稱
   ```
4. 存檔後，重新整理 `http://localhost/restaurant_project/` 網頁即會改為讀寫本機資料庫。
