# Booking Process
 
Tài liệu này mô tả luồng **FastTrack Intl Flight** theo format:
 
- `UI Field Label`
- `field tương ứng trong payload API`
- `option/value mapping`
 
## Scope
 
- Trang: `app/Modules/FastTrack/resources/js/Pages/IntlFlight/Index.vue`
- Step 1: chọn dịch vụ + sân bay + package + option phụ
- Step 2: thông tin hành khách
- Step 3: review + submit payload
- API submit: `POST /api/fast-track-bookings/web-booking`
 
## Data flow ngắn
 
1. Step 1 ghi dữ liệu vào `bookingData.immigration` / `bookingData.emigration`.
2. `PriceBar` tính giá tạm từ `resources/js/utils/pricingHelpers.js`.
3. Step 2 merge dữ liệu cá nhân vào `bookingData`.
4. Step 3 render lại toàn bộ data và gọi `prepareApiData()`.
5. `prepareApiData()` map sang payload API.
 
## Step 1 — Chọn dịch vụ
 
### 1.1 Chọn chiều booking
 
| UI Field Label | field tương ứng trong payload API | option/value mapping |
|---|---|---|
use_departure_fast_track : 1 (bổ sung field này vào payload nếu khách book xuất cảnh)
| 予約情報の組み合わせ | `booking_type` | `both` = có cả 2 chiều, `arrival` = chỉ nhập cảnh, `departure` = chỉ xuất cảnh |
 
### 1.2 Airport
 
| UI Field Label | field tương ứng trong payload API | option/value mapping |
|---|---|---|
| ご利用の対象空港 / Sân bay áp dụng | `arrival_airport`, `departure_airport_code` | `0=SGN`, `1=DAD`, `2=HAN`, `3=PQC` |
 
### 1.3 Entry fast track package
 
| UI Field Label | field tương ứng trong payload API | option/value mapping |
|---|---|---|
| 入国ファストトラックパッケージ | `entry_fast_track_option` | Value phụ thuộc sân bay |
 
**DAD / HAN**
 
| Value | Label |
|---:|---|
| `0` | VIP_IN1_入国審査での優先レーンのみ利用(フィー：35$ ) |
| `1` | VIP_IN2_入国審査での優先レーン利用＋空港外の迎え場所への案内 (フィー：40$ ) |
| `2` | VIP_IN3_ 入国審査での優先レーン利用＋荷物受取サポート＋空港外の迎え場所への案内 (フィー：50$ ) |
| `3` | VIP_IN6_VVIP最優先レーン利用・Non-stopパッケージ(フィー：300$) |
 
**SGN**
 
| Value | Label |
|---:|---|
| `4` | IN_プライオリティ（35$）: 通常の優先入国審査ライン利用 |
| `5` | IN_プライオリティプラス（50$）: 通常の優先入国審査ライン利用＋空港外の迎え場所までご案内 |
| `6` | IN_プレミアム（60$）: 最速の優先入国審査ライン利用 |
| `3` | VIP_IN6_VVIP最優先レーン利用・Non-stopパッケージ(フィー：300$) |
| `7` | IN_ブラック（300$）: 立ち止まらず入国審査通過 |
 
**PQC**
 
| Value | Label |
|---:|---|
| `4` | IN_プライオリティ（35$）: 通常の優先入国審査ライン利用 |
| `5` | IN_プライオリティプラス（50$）: 通常の優先入国審査ライン利用＋空港外の迎え場所までご案内 |
 
### 1.4 Immigration addons
 
| UI Field Label | field tương ứng trong payload API | option/value mapping |
|---|---|---|
| オプション：15分以内に入国審査手続き完了 | `use_immigration_fast_track` | `false` = 利用しない, `true` = 利用する (15$) |
| 飛行機の降り口（または飛行機からバスで到着した場所）でお迎えのご利用を選択してください: | `tarmac_pickup` | `false` = 利用しない, `true` = ご利用する (60$) |
| 迎車利用 | `pickup_service` | `0=利用しない`, `1=迎車 4席 (20$)`, `2=迎車 7席 (25$)`, `3=迎車 9席 Limousine (50$)` |
| SGN online declaration support | `needs_declaration_support` | `1` = 希望する, `0` = 希望しない |
 
### 1.5 Flight info / arrival fields
 
| UI Field Label | field tương ứng trong payload API | option/value mapping |
|---|---|---|
| フライトの予約番号や予約コード | `arrival_flight_reservation_code` | text |
| 便・フライトNo. | `arrival_flight_number` | text |
| 到着日 | `arrival_date` | `YYYY-MM-DD` |
| 到着時間 | `arrival_time` | `HH:MM` |
| お迎えのベトナム語を話せる方の電話番号（任意） | `arrival_phone_number` | text |
| ご要望 | `arrival_request` | text |
| クラスの書類 | `arrival_class_documents` | `economy`, `business` |
| 預かり荷物有無 | `arrival_checked_baggage_availability` | `available`, `not_available`, `undecided` |
 
### 1.6 Departure fields
 
| UI Field Label | field tương ứng trong payload API | option/value mapping |
|---|---|---|
| ご利用の対象空港 | `departure_airport_code` | `0=SGN`, `1=DAD`, `2=HAN`, `3=PQC` |
| 出国Fasttrack / 出国ファストトラックパッケージ | `departure_fast_track_option` | Value phụ thuộc sân bay |
 
**DAD / HAN**
 
| Value | Label |
|---:|---|
| `0` | 出国Fasttrackフルサポートをご利用する（50$) |
| `1` | OUT_スーパーVIP（300$）: 手続きは代行対応で待ち時間ゼロ（ノンストッププラン） |
 
**SGN**
 
| Value | Label |
|---:|---|
| `2` | OUT_プライオリティ（65$）: チェックイン＋優先出国審査＋保安検査サポート |
| `3` | OUT_プレミアム（150$）: プライオリティ利用かつ出国審査に待ち時間ゼロ |
| `1` | OUT_スーパーVIP（300$）: 手続きは代行対応で待ち時間ゼロ（ノンストッププラン） |
 
**PQC**
 
| Value | Label |
|---:|---|
| `4` | OUT_プライオリティ（50$）: チェックイン＋優先出国審査＋保安検査サポート |
| `1` | OUT_スーパーVIP（300$）: 手続きは代行対応で待ち時間ゼロ（ノンストッププラン） |
 
| UI Field Label | field tương ứng trong payload API | option/value mapping |
|---|---|---|
| 出発日 | `departure_date` | `YYYY-MM-DD` |
| 出発空港での待ち合わせご希望時間 | `pickup_time` | `HH:MM` |
| お見送りのベトナム語を話せる方の電話番号（任意） | `departure_phone_number` | text |
| ご要望 | `departure_request` | text |
| クラスの書類 | `departure_class_documents` | `economy`, `business` |
| 預かり荷物有無 | `departure_checked_baggage_availability` | `available`, `not_available`, `undecided` |
| 席のご希望 | `departure_seating_preferences` | `0`〜`9` |
 
## Step 2 — Thông tin hành khách
 
| UI Field Label | field tương ứng trong payload API | option/value mapping |
|---|---|---|
| 姓 / Last name | `last_name` | text |
| 名 / First name | `first_name` | text |
| 性別 / Gender | `sex` | `0=男性`, `1=女性` |
| 生年月日 / Date of birth | `date_of_birth` | `YYYY-MM-DD` |
| 国コード 付電話番号 / Phone number with country code | `user_phone_number` | text |
| 国籍 / Nationality | `nationality` | country code text |
| 案内を受取るためのメールアドレス | `contact_email_to` | email |
| CCを希望されるメールアドレス | `contact_email_cc` | email |
| パスポート No. | `passport_number` | text |
| パスポートの有効期限 | `passport_expiry_date` | `YYYY-MM-DD` |
| 会社名 | `optional_company_name` | text |
| 紹介の方のお名前 | `referred_by_name` | text |
| Line OA追加 / contact method | `contact_method` | options bên dưới |
| 弊社のファストトラックサービスはどのチャンネルから知りましたか？ | `survey_channel` | options bên dưới |
| 以下のサービスについての無料相談をご希望しませんか。 | `add_ons` | mảng value string |
 
### 2.1 `contact_method`
 
| Value | Label |
|---:|---|
| `0` | 加してメッセージ送った |
| `1` | 後でLINE追加する |
| `2` | メールだけ希望（空港で対応遅れる可能性ある） |
| `3` | 電話だけ希望（料金やローミングの問題発生可能性ある） |
| `4` | 上の番号のZALOで連絡希望 |
| `5` | 空港で連絡手段なし、相談したい |
 
### 2.2 `survey_channel`
 
| Value | Label |
|---:|---|
| `0` | 知り合いのご紹介 |
| `1` | サービス紹介メール |
| `2` | Facebook |
| `3` | 広告 |
| `4` | 検索サイト（Google、Yahooなど） |
| `5` | 再利用 |
 
### 2.3 `add_ons`
 
| Value | Label |
|---:|---|
| `0` | 空港ラウンジ |
| `1` | 日本人や外国人観光客向けのレストラン |
| `2` | 日本人や外国人観光客向けのホテル |
| `3` | マッサージ・健康ケア・美容ケア |
| `4` | ショッピングスポット |
| `5` | 通訳・観光案内 |
| `6` | レンタルカー |
| `7` | ゴルフ |
| `8` | 航空券（購入・変更等） |
| `9` | ベトナムサプライヤー探し・ベトナム会社繋がり |
 
## Step 3 — Review / submit
 
`prepareApiData()` trong `BookingStep3.vue` dựng payload cuối.
 
| UI Field Label / derived value | field tương ứng trong payload API | option/value mapping |
|---|---|---|
| 支払方法 | `payment_method` | `0=現金払い`, `1=オンラインでクレジット決済`, `2=ベトナム口座振込` |
| 仮計算 | `preliminary_calculation` | subtotal sau combo discount, trước coupon |
| クーポン割引額 | `coupon_discount_amount` | số tiền coupon |
| 割引特典 | `two_way_discount` | `5.00` nếu chọn cả 2 chiều, ngược lại `0.00` |
| 夜間・早朝対応費 | `night_surcharge_value` | phí phụ thu |
| 税金 | `tax` | VAT 8% |
| 合計 | `total` | tổng cuối cùng |
 
## Internal-only fields
 
Các field dưới đây có trong UI/state nhưng không gửi trực tiếp như field độc lập trong payload:
 
- `sameAsEntry`
- `useOtherOptions`
- `departure_driver_phone_number_and_route_info_confirmed`
 
## Notes
 
- `PriceBar` hiện chỉ đóng vai trò tính giá tạm và đẩy dữ liệu giá ngược lên `bookingData`.
- `two_way_discount` là field riêng cho combo discount `$5`.
- `booking_type` được tính từ trạng thái chọn chiều booking, không phải input riêng.
 
 
 
em đọc và đối chiếu với trang booking nha 
ベトナムファストトラック | VJP Fast Track
VJP Fast Track
 
Khi booking em có thể bấm pause ở tab network để check phần payload data được gửi đến api 

cơ bản là các thông tin mà người dùng chọn mình sẽ cho người dùng được phép thay đổi, phần price cần có công thức tính tự động khi khách thay đổi các option dịch vụ (có giá )
 
日本人向けのベトナムファストトラック予約サービス。ベトナム主要空港での入国・出国サポートを日本語でご案内します。
 