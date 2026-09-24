# php-advanced-learning

PHP 8.5 の内部挙動検証、型安全設計、およびモダンなプログラミングパラダイム（第一級 Callable、関数合成、並行処理基盤など）を探究・実証するためのリポジトリです。

---

## 概要

本プロジェクトでは、PHP の言語仕様や Zend Engine の内部挙動（zval メモリモデル、Copy-on-Write、型ジャグリング境界など）およびモダンな言語機能について、**Pest によるテストコード** と **PHPStan (Level: max) による静的解析** を通じて体系的に検証・実装しています。

---

## 技術スタック

- **言語**: PHP 8.5 CLI (Alpine Linux)
- **テストフレームワーク**: [Pest v3](https://pestphp.com/)
- **静的解析**: [PHPStan v2](https://phpstan.org/) (Level: `max`)
- **デバッグ/プロファイル**: Xdebug 3
- **コンテナ基盤**: Docker / Docker Compose
- **タスクランナー**: GNU Make

---

## ディレクトリ構造

```text
php-advanced-learning/
├── Makefile                 # 開発タスクランナー (test, analyze, check など)
├── compose.yaml             # Docker Compose 構成定義
├── docker/
│   ├── Dockerfile           # PHP CLI + Xdebug + Composer
│   └── php.ini              # 開発用 PHP / Xdebug 設定
├── ROADMAP.md               # カリキュラム・検証テーマ一覧
├── LEARNING_LOG.md          # 技術検証ログ・考察記録
├── composer.json            # 依存関係定義
├── phpstan.neon             # PHPStan 設定
├── src/                     # 本実装コード (App\)
└── tests/                   # Pest テストコード (Tests\)
    ├── Pest.php
    └── Unit/                # 検証テストスイート
```

---

## コマンドリファレンス

日常の開発・検証は `make` コマンドを経由してコンテナ内で実行します。

| コマンド | 説明 |
| :--- | :--- |
| `make build` | Docker コンテナイメージをビルド |
| `make test` | Pest テストを実行 |
| `make analyze` | PHPStan 静的解析を実行 (Level: max) |
| `make check` | テストと静的解析を両方実行 (`test` + `analyze`) |
| `make composer ARGS="..."` | Composer コマンドを実行 (例: `make composer ARGS="install"`) |
| `make shell` | コンテナ内の対話シェル (sh) を起動 |

---

## セットアップと実行

```bash
# 1. コンテナのビルド
make build

# 2. 依存パッケージのインストール
make composer ARGS="install"

# 3. テストと静的解析の実行
make check
```
