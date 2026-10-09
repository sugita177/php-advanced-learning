# Phase 8: モダン型安全アーキテクチャと Enum の深層

---

## Lesson 8.1: Pure Enum / Backed Enum の内部仕様と完全網羅性検査 (Exhaustiveness Check)

- **検証日**: 2026-10-09
- **検証コード**:
  - 実証スクリプト: `src/Phase8/Lesson8_1_EnumBasicsAndExhaustiveness.php`
  - Pestテスト: `tests/Unit/Phase8/Lesson8_1_EnumBasicsAndExhaustivenessTest.php`

### 1. 検証した言語仕様・テーマ
- PHP 8.1 で導入された Enum（列挙型）のオブジェクトモデルとシングルトン性。
- Pure Enum（値を持たない純粋列挙型）と Backed Enum（`int` または `string` のバッキング値を持つ列挙型）の厳密なプロパティ境界。
- `name` プロパティ（全 Enum 共通の識別子文字列）と `value` プロパティ（Backed Enum 限定）。
- Pure Enum に対する `->value` アクセス時の Zend Engine 挙動（`Error` 例外ではなく `E_WARNING: Undefined property` が発行されて `null` を返す仕様）。
- `from()`（見つからない場合は `ValueError` スロー）と `tryFrom()`（見つからない場合は `null` 返却）の境界。
- Enum のクローン禁止仕様（`clone` 時に `Error: Trying to clone an uncloneable object`）。
- `match` 式による完全網羅性検査（Exhaustiveness Check）と `default` 句の不要性。

### 2. 実施したテスト・検証概要
- Pure Enum のシングルトン同一性とクローン禁止:
  - `Suit::Hearts === Suit::Hearts` が同一インスタンスとして厳密に一致することを検証。
  - `clone Suit::Hearts` を実行すると即座に `Error` がスローされることをアサーション。
  - `ReflectionEnum` を用いて、`isBacked()` が `false` であり、`value` プロパティが存在しないことを実証。
- Backed Enum の生成とエラーハンドリング:
  - `TaskStatus::from('pending')` で該当 Case が復元されること。
  - `TaskStatus::tryFrom('unknown')` が例外を投げずに `null` を返すこと。
  - `TaskStatus::from('unknown')` が `TypeError` ではなく `ValueError` をスローすることを検証。
- `match` 式による網羅性検査:
  - 全 Case（Pending, InProgress, Done）を処理する `match` 式を実装し、`default` 句なしで全ステータスが漏れなく日本語ラベルにマッピングされることを実証。

### 3. 直面した落とし穴・内部挙動の気付き
- **Pure Enum への `->value` アクセスは `Error` ではなく `Undefined property Warning`**:
  - Pure Enum に `->value` でアクセスすると構文エラーや例外が飛ぶと思われがちだが、Zend Engine 内部では「`value` プロパティを持たない通常のオブジェクト」として扱われるため、通常の未定義プロパティと同じく `E_WARNING` が発生し、値として `null` が返される。
  - 静的解析（PHPStan Level: max）では、未定義プロパティアクセスとして厳密にブロックされる。
- **値不一致時の例外型は `ValueError`**:
  - `from()` に無効な値を渡したときの例外は `InvalidArgumentException`（SPL）ではなく、PHP 8.0 で新設された `ValueError`（`Error` 傘下）である。

### 4. 実務・Qiita 向けのアウトプット要点
- `match` 式と Enum を組み合わせる最大の利点は、**将来 Case が追加された際に静的解析（PHPStan）が修正漏れ箇所を即座にエラーとして検知してくれる点** である。安易に `default` 句を書いてしまうとこの網羅性検査が無効化されるため、ドメインロジックでは `default` を書かないのがモダン PHP の定石。
- 文字列や数値をそのまま引き回す「プリミティブ執着（Primitive Obsession）」を排除し、DB やリクエスト境界で `tryFrom()` を使って即座に Backed Enum へ変換することで、以降のビジネスロジック全体を完全な型安全に保つことができる。
