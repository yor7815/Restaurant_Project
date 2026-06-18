# Restaurant_Project / 紅番茄餐廳顧客回饋管理系統

本專案是一個基於 **關聯式資料庫 (MySQL/MariaDB)** 與 **PHP** 開發的「紅番茄餐廳顧客回饋管理系統」。提供直觀且美觀的網頁使用介面，並完整實作了資料庫新增 (Insert)、刪除 (Delete)、修改 (Modify)、多表連接查詢 (Query with JOIN)、資料庫交易防護 (Transaction) 以及進階複雜統計報表。

本專案更進一步加入了**角色基底登入與註冊系統 (RBAC)**，將使用者依權限隔離至專屬儀表板，並在全系統頁面實施嚴格的 Session 安全驗證。

---

## 💻 使用的程式語言與技術 (Technologies & Languages)
* **後端 (Backend)**：`PHP` (採用 `mysqli` 模組連結資料庫，並使用 Prepared Statements 防範 SQL Injection、Session 驗證角色權限)
* **前端 (Frontend)**：`HTML5`、`CSS3` (現代化卡片式響應佈局、玻璃擬態與 CSS 變數)、`JavaScript` (DOM 元素動態處理、餐點金額折扣動態計算、動態下拉選單聯動篩選)
* **資料庫 (Database)**：`SQL` (MySQL / MariaDB DDL 結構、外鍵級聯、Transaction 交易防護、進階複雜查詢)
* **伺服器 (Server)**：Apache (XAMPP / AppServ 整合套件)

---

## 🔑 系統登入帳密與角色權限說明

系統設有角色跳轉路由（`index.php`），當使用者登入或直接進入根目錄時，會根據 Session 中的角色身分自動導向至對應的主畫面：

| 角色權限 | 登入帳號範例 | 預設密碼 | 導向主畫面與功能權限 |
| :--- | :--- | :--- | :--- |
| **顧客 (Customer)** | 註冊的電話號碼 (例如 `0911222333`) | 自訂 (或預設 `123`) | 自動導向至**顧客專區 (`customer_dashboard.php`)**。僅能使用「填寫/新增顧客回饋表單」功能。 |
| **服務員 (Staff)** | 員工編號 (例如 `S001`) | `123` | 自動導向至**服務員專區 (`staff_dashboard.php`)**。能使用「建立用餐紀錄 (`create_record.php`)」及「用餐紀錄一覽 (`record_list.php`)」管理功能。 |
| **經理 (Manager)** | `admin` | `114514` | 自動導向至**管理者首頁 (`manager_home.php`)**。擁有「進階統計表」、「管理者首頁（營運概況/關注低分回饋/員工評分排行）」、「顧客總覽」、「員工總覽」及「回饋表單總覽」等完整管理權限。 |

---

## 🛠️ 系統功能特色

### 1. 角色跳轉與 Session 權限隔離
* **自動路由跳轉 (`index.php`)**：統一根目錄入口，依登入角色權限派發至各自的控制台頁面。
* **越權攔截防護**：全系統關鍵管理頁面皆在最上方置入 Session 驗證，非授權角色若嘗試直接輸入 URL 越權存取，將自動被重導向至登入頁。
* **顧客註冊功能 (`register.php`)**：顧客可在登入頁面點擊「立即註冊」，輸入姓名、性別、年齡、電話及密碼進行註冊。系統會自動查詢資料庫最大 ID，生成下一個 `Customer_ID` (如 `C004` 等) 並寫入資料庫，密碼以明文存於資料庫的 `password` 欄位中。

### 2. 經理管理後台 (`manager_home.php` & `feedback_list.php` 等)
* **營運概況首頁 (`manager_home.php`)**：經理登入後之首頁，提供「總回饋數」、「整體平均評分」、「員工平均評分」、「累計消費金額」等四大營運指標，並列出待關注之低分回饋以及員工服務排行。
* **回饋表單總覽 (`feedback_list.php`)**：整合多表連接查詢 (`JOIN`) 呈現顧客姓名、用餐桌號、整體評分與回饋意見，並提供即時模糊搜尋功能。
* **資料管理表格**：
  * **顧客總覽 (`customer_data.php`)**：管理顧客基本資料、查詢及編輯會員等級。
  * **員工總覽 (`staff.php`)**：管理及查詢員工編號、姓名與職位。
  * **員工評分統計 (`staff_stats.php`)**：統計員工服務平均分數、五星數、低分數，並條列細部的服務意見。

### 3. 服務員管理控制台 (`staff_dashboard.php` & `record_list.php` 等)
* **建立用餐紀錄 (`create_record.php`)**：新增顧客用餐消費紀錄，並能動態點選多項點購餐點與設定折扣。
* **用餐紀錄一覽 (`record_list.php`)**：列出所有用餐消費紀錄（包含顧客、服務員與點購餐點 x 數量）。服務員可在此進行用餐紀錄的修改與刪除，前端以 JavaScript 自動累加餐點價格、動態計算折扣與最終總金額，並以資料庫交易 (Transaction) 進行更新。

### 4. 動態多項顧客回饋新增 (`create.php`)
* **動態聯動篩選**：
  * 選擇顧客後，「消費紀錄」下拉選單僅會列出該名顧客的消費。
  * 選擇消費紀錄後，系統會自動快取該訂單，讓「餐點評價」下拉選單僅顯示該訂單實際點過的餐點，且「外場服務員」也僅顯示當時服務該桌的同仁。
* **動態多餐點評價**：支援顧客點擊 `➕ 增加餐點評價` 按鈕，自由評價一至多道餐點，並支援動態移除。
* **資料庫交易 (Transaction)**：後端以 ACID 交易安全機制包裹，確保主要回饋、弱實體細節與評分關聯表同時寫入成功，否則自動 Rollback 撤銷。

### 5. 回饋資料修改與級聯刪除 (`update.php`, `delete.php`)
* **動態編輯**：自動載入舊有回饋內容與多個已評價的餐點卡片，經理可自由修改、增加或刪除細部評論，後端同樣以交易防護更新。
* **外鍵級聯刪除 (`ON DELETE CASCADE`)**：點擊刪除後，資料庫將利用外鍵級聯機制，在刪除主回饋表單的同時**自動清除**對應的弱實體明細與評分連結，確保資料庫一致性。

### 6. 進階統計報表 (`advanced_query.php`)
呈現 5 大複雜 SQL 查詢，介面已清除 Raw SQL 程式碼區塊與技術名詞，只顯示清晰的管理報表：
1. **餐點平均滿意度與點餐熱門次數** (AVG + COUNT + JOIN + GROUP BY)
2. **外場員工服務滿意度統計** (AVG + COUNT + JOIN + GROUP BY)
3. **高於平均整體評分的回饋表單** (Set Comparison / 子查詢)
4. **給過 5 星滿分好評的顧客名單** (Set Membership / IN 子查詢)
5. **各顧客點購最多次的餐點** (CTE + Window Function `RANK()`)

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
*(系統會預設導向 `login.php` 登入畫面)*

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
