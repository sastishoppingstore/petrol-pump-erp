# Vital Petroleum ERP — Operations & User Guide
## مہر فلنگ اسٹیشن، شیخوپورہ — یوزر گائیڈ و طریقہ کار

**Franchise:** Vital Petroleum Pakistan  
**Station:** Mehar Filling Station (Gujranwala Road, Sheikhupura)  
**System Version:** 2.0 (Laravel 12 / PHP 8.3 / Livewire 3)  
**Currency & Units:** PKR (Rs. / پاکستانی روپیہ), Volume (Litres / لیٹر), Decimals: Money (14,2), Litre (12,3)

---

## Table of Contents / فہرست
1. [Forecourt Operations & POS (سیلز اور پی او ایس)](#1-forecourt-operations--pos)
2. [Shift Management & Cash Handover (شفٹ مینجمنٹ اور روکڑ کیش)](#2-shift-management--cash-handover)
3. [Fuel Purchase & Tank Decantation (ایندھن خریداری و ترسیل)](#3-fuel-purchase--tank-decantation)
4. [Customer Udhaar & Credit Control (ادھار کھاتہ اور وصولیاں)](#4-customer-udhaar--credit-control)
5. [Operating Expenses & Petty Cash (اخراجات اور پیٹی کیش)](#5-operating-expenses--petty-cash)
6. [Double-Entry General Ledger (ڈبل انٹری جنرل لیجر)](#6-double-entry-general-ledger)
7. [Daily Closing Wizard & Day Lock (روزانہ اختتامی وزرڈ اور لاک)](#7-daily-closing-wizard--day-lock)
8. [22 Core Reports Hub (رپورٹس اور گوشوارے)](#8-22-core-reports-hub)
9. [Database Backup Engine (ڈیٹابیس بیک اپ سسٹم)](#9-database-backup-engine)

---

## 1. Forecourt Operations & POS
### انگریزی اور اردو طریقہ کار: سیلز اور پی او ایس

The Point of Sale (POS) screen is optimized for high-speed touchscreens and mobile devices. It connects directly with dispenser nozzles, fuel pricing, and cash reconciliation.

### Step-by-Step Flow:
1. **Login:** Cashier enters 4-digit PIN or email credentials.
2. **Select Active Dispenser & Nozzle:** Tap dispenser (e.g., Dispenser 1 - Super Petrol / HSD Diesel).
3. **Specify Sale Volume or Amount:**
   - Preset buttons: Rs. 500, Rs. 1,000, Rs. 2,000, Full Tank (پورا ٹینک).
   - Or enter exact litres (e.g., `35.250` L).
4. **Choose Tender / Payment Mode:**
   - **Cash (نقد):** System calculates change return automatically.
   - **Credit / Udhaar (ادھار):** Select customer by code or name; system checks credit limit and vehicle number.
   - **Card / Fleet (بینک کارڈ):** Records terminal transaction reference.
5. **Print Receipt:** Thermal printer outputs 80mm/58mm Urdu/English receipt with FBR fiscal QR code.

> **اردو ہدایات:**  
> کیشیئر لاگ ان کے بعد نوزل منتخب کر کے رقم یا لیٹر کا اندراج کریں۔ نقد، ادھار، یا کارڈ کے ذریعے پیمنٹ وصول کریں اور فوری تھرمل رسید پرنٹ کریں۔ اگر گاہک کا ادھار ہو تو گاڑی کا نمبر درج کرنا لازمی ہے۔

---

## 2. Shift Management & Cash Handover
### شفٹ کا آغاز، اختتام اور کیش ہینڈ اوور

Forecourt transparency requires clean shift demarcations with meter tracking and cash variance calculations.

### Workflow:
1. **Shift Opening (شفٹ کا آغاز):**
   - Manager assigns Cashiers to dispensers.
   - Records starting meter readings on each nozzle.
   - Issues Opening Cash Float (ابتدائی روکڑ) to each cashier.
2. **Shift Closing (شفٹ کا اختتام):**
   - Record Closing Mechanical and Electronic Meter Readings.
   - Volume Dispensed = `Closing Meter - Opening Meter - Approved Nozzle Tests`.
   - Count actual cash present in the drawer.
   - System calculates: `Expected Cash = Opening Float + Cash Sales - Drops`.
   - Variance (Short / Over) is flagged:
     * Over (+): Marked green, credited to Cash Over.
     * Short (-): Marked red, flagged for manager sign-off.
3. **Manager Approval:** Shift status transitions to `APPROVED` and posts to the General Ledger.

---

## 3. Fuel Purchase & Tank Decantation
### ایندھن خریداری، ٹینکر آمد اور پیمائش (ڈِپ چارٹ)

### Workflow:
1. **Tanker Arrival (تیل ٹینکر کی آمد):**
   - Verify Challan Number, Tanker Registration, and Driver Credentials.
   - Measure Underground Tank Dip before decantation using dipstick.
   - Apply water-finding paste to check for water contamination.
2. **Fuel Decantation (ٹینکر خالی کرنا):**
   - Unload fuel into assigned underground tank.
   - Measure Underground Tank Dip immediately after decantation.
   - System applies Tank Calibration Dip Chart to convert centimeters (cm) to exact litres.
3. **Shortage Calculation & Claims (قلت اور دعویٰ):**
   - If `Volume Received < Volume Ordered`, system automatically logs shortage litres and shortage amount.
   - Status set to `PENDING CLAIM` against oil marketing company or transporter.
4. **Weighted-Average Costing & Supplier Credit:**
   - System recalculates inventory unit cost:  
     $$\text{New Cost} = \frac{(\text{Stock}_{\text{old}} \times \text{Cost}_{\text{old}}) + (\text{Litres}_{\text{received}} \times \text{Purchase Rate})}{\text{Stock}_{\text{old}} + \text{Litres}_{\text{received}}}$$
   - Supplier ledger is credited automatically with invoice total.

---

## 4. Customer Udhaar & Credit Control
### کسٹمر ادھار کھاتہ اور ریکوری وصولیاں

Credit fuel sales to local transport companies, factories, and agricultural clients are tracked strictly:

1. **Credit Limit & Ledger:**
   - Every customer has a designated Credit Limit (حدِ ادھار) and credit days limit (e.g. 15 or 30 days).
   - If outstanding exceeds the credit limit, the POS warns or locks further sales without manager authorization.
2. **Udhaar Recovery (رقم کی وصولی):**
   - Navigate to **Accounts > Customer Payments**.
   - Select Customer, amount received, and method (Cash / Cheque / Bank Transfer).
   - System updates customer balance, creates a receipt voucher, and posts debit to Bank/Cash and credit to Accounts Receivable.
3. **Customer Statement (کھاتہ اسٹیٹمنٹ):**
   - Filter by date range; export PDF or print with opening balance, chronological debit/credit rows, and closing balance in Lakh format.

---

## 5. Operating Expenses & Petty Cash
### کاروباری اخراجات اور پیٹی کیش واؤچرز

Routine operational expenditures (electricity bills, generator diesel, staff meals, maintenance) are tracked through pre-categorized expense vouchers:

1. Click **Expenses > Record Expense**.
2. Select Category: *Electricity & Utilities, Generator Maintenance, Staff Welfare, Municipal Tax, Office Supplies*.
3. Choose Payment Method: *Cash in Hand (پیٹی کیش)* or *Bank Account (بینک چیک/آن لائن)*.
4. Upload receipt photo or bill attachment.
5. System posts expense voucher and debits Operating Expenses in General Ledger.

---

## 6. Double-Entry General Ledger
### ڈبل انٹری جنرل لیجر اور میزان نامہ (Trial Balance)

Vital Petroleum ERP runs a full Pakistani double-entry general ledger under Standard Petroleum Chart of Accounts:

- **Automatic Postings:**
  * Sales $\rightarrow$ Dr. Cash / AR, Cr. Fuel Revenue, Cr. Sales Tax.
  * Purchases $\rightarrow$ Dr. Fuel Inventory, Cr. Accounts Payable.
  * Payments $\rightarrow$ Dr. Accounts Payable, Cr. Cash / Bank.
  * Recoveries $\rightarrow$ Dr. Cash / Bank, Cr. Accounts Receivable.
  * Shift Variance $\rightarrow$ Dr./Cr. Cash Short & Over Account.
  * Voids $\rightarrow$ Automatic symmetrical reversal journal entries.
- **Manual Journal Entries:**
  * Go to **Accounts > Journals > New Journal Entry**.
  * Enter Date, Narration, and at least 2 lines.
  * Interactive Alpine.js balance validator ensures $\sum \text{Debits} = \sum \text{Credits}$ before submission.
- **Trial Balance (میزان نامہ):**
  * Instant balance check of all Asset, Liability, Equity, Revenue, and Expense accounts.

---

## 7. Daily Closing Wizard & Day Lock
### روزانہ اختتامی وزرڈ اور تاریخ کا باضابطہ لاک

At the end of every business day (usually midnight or 06:00 AM), the Station Manager executes the **Daily Closing Wizard**:

### The 4-Point Verification Checklist:
1. **Shifts Verified (تمام شفٹوں کی بندش):**
   - Confirms all forecourt cashiers have closed and submitted their shifts. No shift may remain `OPEN`.
2. **Physical Tank Dips (ٹینکیوں کی فزیکل پیمائش):**
   - Physical dip rod measurement must be entered for every active underground tank.
   - Dip volume is compared against computerized Book Stock; variance litres are recorded.
3. **Physical Cash Counted (محفوظ کیش کی گنتی):**
   - Manager counts total currency notes and coins in the station safe.
   - System cross-checks: $\text{Opening Cash} + \text{Cash Sales} + \text{Udhaar Receipts} - \text{Expenses} - \text{Bank Deposits}$.
   - Exact cash difference (Short / Over) is documented.
4. **Bank Deposits (بینک میں جمع شدہ رقوم):**
   - Confirms all cash handovers deposited to commercial banks (HBL, Meezan, MCB) have deposit slips logged.

### Day Locking & Automation:
- Once approved, the business day is marked **LOCKED**.
- Financial period lock prevents subsequent edits or backdated sales.
- Automated email summary with complete day metrics is dispatched to the station owner.

---

## 8. 22 Core Reports Hub
### 22 بنیادی اور جامع رپورٹس کا مرکز

Access all operational, financial, inventory, and staff reports under `/reports`. Every report supports **Today, Yesterday, Last 7 Days, Last 15 Days, This Month, This Year, By Shift, or Custom Date Range**, with 1-click **Print**, **PDF Export**, and **Excel/CSV Export**.

| Category | Report Title | Urdu Name | Key Insights |
|---|---|---|---|
| **Sales** | Daily Sales Summary | سیلز سمری اور تفصیل | Invoices, cashier breakdown, litres, cash/credit turnover |
| **Sales** | Fuel Sales by Product | ایندھن فروخت بلحاظ مصنوعات | Super, Diesel, HOBC litres, cost, gross margins |
| **Sales** | Nozzle Sales Report | نوزل فروخت رپورٹ | Dispenser meter opening, closing, net litres dispensed |
| **Sales** | Meter Reading Audit | میٹر ریڈنگ آڈٹ | Physical pump totalizers audit trail and corrections |
| **Sales** | Cashier Performance | کیشیئر کارکردگی رپورٹ | Cashier volume, sales cash collected, variance track |
| **Cash & Bank** | Cash Book (Roznamcha) | کیش بک (روکڑ کھاتہ) | Daily receipts, disbursements, running till balance |
| **Cash & Bank** | Bank Book | بینک بک کھاتہ | Deposits, cheques cleared, commercial bank balances |
| **Cash & Bank** | Cheque Register | چیک رجسٹر | Post-dated, deposited, and cleared cheques |
| **Cash & Bank** | Daily Closing History | روزانہ اختتامی ریکارڈ | Historical records of locked daily closings and metrics |
| **Udhaar** | Customer Ledger | کسٹمر لیجر / کھاتہ | Statement with debit fuel slips, credit receipts, balance |
| **Udhaar** | Customer Outstanding | کسٹمر بقایا جات | Total outstanding balances against credit limits |
| **Udhaar** | Customer Ageing Analysis | کسٹمر ایجنگ رپورٹ | Overdue fuel debts: 0-30, 31-60, 61-90, 90+ days |
| **Suppliers** | Supplier Ledger | سپلائر کھاتہ / لیجر | Fuel purchases vs payments made to OMC / oil suppliers |
| **Suppliers** | Supplier Payable | سپلائر واجب الادا رقوم | Outstanding balances owed to each petroleum supplier |
| **Suppliers** | Purchase & Decantation | ایندھن خریداری و ترسیل | Tanker receipts, challans, shortage litres and claims |
| **Stock** | Tank Stock & Variance | ٹینکی اسٹاک اور ڈِپ فرق | Book stock vs dip volume; temperature/evaporation gain/loss |
| **Stock** | Price-Change Gain/Loss | قیمت تبدیلی نفع و نقصان | OGRA price revision stock revaluation windfall or deficit |
| **Financial** | Profit & Loss Statement | نفع و نقصان گوشوارہ | Net revenue, COGS, gross margin, operating expenses, net profit |
| **Financial** | Balance Sheet | بیلنس شیٹ | Assets (Cash, Bank, Fuel, AR) vs Liabilities (AP, Taxes) |
| **Financial** | Trial Balance | میزان نامہ (ٹرائل بیلنس) | Summary of all account balances validating debit = credit |
| **Financial** | Operating Expenses | کاروباری اخراجات رپورٹ | Breakdown by category (electricity, payroll, maintenance) |
| **HR** | Staff & Salary Report | عملہ اور تنخواہ رپورٹ | Basic salaries, advances, overtime, attendance, net payable |

---

## 9. Database Backup Engine
### پی ایچ پی ڈیٹا بیس بیک اپ اور ڈیٹا کی حفاظت

The backup engine functions entirely in pure PHP with zero dependency on the external `mysqldump` command line binary, making it completely reliable on StackCP, cPanel, and cloud VPS:

1. Navigate to **System > Backup & Restore**.
2. **Options Available:**
   - **Create Database Backup (SQL.GZ):** Streams unbuffered SQL rows in chunks of 200 directly into a gzip compressed file. Safe for low-RAM servers.
   - **Create Full Archive (ZIP):** Creates an archive containing the database SQL dump alongside all uploaded documents, supplier bills, and invoice attachments.
3. **Secure Download:**
   - Every backup is protected with a SHA-256 HMAC temporary signed URL expiring in 120 minutes.
   - Prevents unauthorized public file scraping.
4. **Restoration:**
   - Uncompress the `.sql.gz` file and import via phpMyAdmin or command line MySQL client.

---
*Vital Petroleum &bull; Mehar Filling Station Sheikhupura &bull; Software Support Helpline: 0300-1234567*
