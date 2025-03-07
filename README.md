# coachtech 勤怠管理アプリ

## 環境構築
**Dockerビルド**
1. `git clone git@github.com:hiroyuki92/time-attendance-management.git`
2. `cd time-attendance-management`     クローンしたディレクトリに移動する
3. DockerDesktopアプリを立ち上げる
4. `docker-compose up -d --build`

**Laravel環境構築**
1. `docker-compose exec php bash`
2. `composer install`
3. 「.env.example」ファイルをコピーして 「.env」ファイルに命名を変更。
```bash
cp .env.example .env
```
4. 「.env.testing.example」ファイルをコピーして 「.env.testing」ファイルに命名を変更。
```bash
cp .env.testing.example .env.testing
```
5. .envに以下の環境変数を追加
``` text
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass
```
6. .env.testingに以下の環境変数を追加
``` text
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=demo_test
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass
```
7. 各環境のアプリケーションキーを生成
``` bash
php artisan key:generate        # .env用
php artisan key:generate --env=testing  # .env.testing用
```

8. マイグレーションとシーディングの実行
``` bash
php artisan migrate --seed
```  
　実行すると以下の初期データが作成されます  
  - 管理者ユーザー  
	メールアドレス: admin@example.com  
	パスワード: password  
	権限: 管理者  
 
  - ランダムな一般ユーザー  
	ダミーユーザーが5人生成されます。  
	メールアドレスや名前はランダムに設定されています。  
	パスワード: password

  - 全一般ユーザーの勤怠記録情報  
	一般ユーザーごとに３ヶ月前まで作成するよう設定しています。  
  
9. メール認証機能の設定  
    #### 機能概要
	このアプリケーションには、ユーザー登録時にメールアドレスを確認するためのメール認証機能が実装されています。この機能により、不正なアカウント作成を防ぎ、アプリケーションのセキュリティを向上させます。
    #### 開発環境でのメール確認方法
	このプロジェクトでは、開発環境でのメール確認にMailHogを使用します。
	MailHog を使用するために、`.env` に以下の設定を追加してください。
	Docker を使うため、`MAIL_HOST=mailhog` を設定することが重要です。
	#### **1️⃣ `.env` の設定**
```text
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=example@test.com
MAIL_FROM_NAME="${APP_NAME}"
```

- **デフォルトポート**: `1025`
- **Web UI**: [http://localhost:8025](http://localhost:8025)  

送信されたメールは MailHog の Web UI ([http://localhost:8025](http://localhost:8025)) で確認できます。



   
10. テストの実行
``` bash
php artisan test
```

## 使用技術(実行環境)
- **PHP** 7.4.9
- **Laravel** 8.83.29
- **MySQL** 8.0.26（Dockerコンテナ）
- **Nginx** 1.27.3（Dockerコンテナ）
- **MailHog**（開発環境でのメール送信テスト用）

## ER図
```mermaid
erDiagram
    USERS {
        bigint id PK
        string name
        string email
        string password
        string role
        datetime created_at
        datetime updated_at
    }
    
    ATTENDANCES {
        bigint id PK
        bigint user_id FK
        date work_date
        time clock_in
        time clock_out
        string status
        datetime created_at
        datetime updated_at
    }
    
    BREAK_TIMES {
        bigint id PK
        bigint attendance_id FK
        time break_start
        time break_end
        datetime created_at
        datetime updated_at
    }
    
    ATTENDANCE_MODIFICATION_REQUESTS {
        bigint id PK
        bigint attendance_id FK
        date requested_work_date
        time requested_clock_in
        time requested_clock_out
        text reason
        enum status
        datetime created_at
        datetime updated_at
    }
    
    BREAK_MODIFICATION_REQUESTS {
        bigint id PK
        bigint attendance_mod_request_id FK
        bigint break_times_id FK
        integer temp_index
        time requested_break_start
        time requested_break_end
        datetime created_at
        datetime updated_at
    }
    
    %% リレーションシップの定義
    USERS ||--o{ ATTENDANCES : ""
    ATTENDANCES ||--o{ ATTENDANCE_MODIFICATION_REQUESTS : ""
    ATTENDANCES ||--o{ BREAK_TIMES : ""
    ATTENDANCE_MODIFICATION_REQUESTS ||--o{ BREAK_MODIFICATION_REQUESTS : ""
    BREAK_TIMES ||--o{ BREAK_MODIFICATION_REQUESTS : ""

```

## URL
.環境開発：http://localhost/  
.phpMyAdmin：http://localhost:8080/
