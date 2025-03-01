-- `demo_test` データベースを作成（存在しない場合のみ）
CREATE DATABASE IF NOT EXISTS demo_test;

-- ユーザーを作成（存在しない場合のみ）
CREATE USER IF NOT EXISTS 'laravel_user'@'%' IDENTIFIED BY 'laravel_pass';

-- ユーザーに権限を付与
GRANT ALL PRIVILEGES ON demo_test.* TO 'laravel_user'@'%';
FLUSH PRIVILEGES;