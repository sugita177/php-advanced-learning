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

---

## Lesson 8.2: Enum へのインターフェース・トレイト・メソッド実装と高度なステートマシン

- **検証日**: 2026-10-10
- **検証コード**:
  - 実証スクリプト: `src/Phase8/Lesson8_2_EnumAdvancedStateMachine.php`
  - Pestテスト: `tests/Unit/Phase8/Lesson8_2_EnumAdvancedStateMachineTest.php`

### 1. 検証した言語仕様・テーマ
- Enum のオブジェクト指向機能の制約と特権（プロパティ宣言の絶対禁止 vs インターフェース・トレイト・メソッド定義の自由度）。
- Enum 内部における `$this` の振る舞い（現在の Case 自身を参照するオブジェクトとしての動作）。
- トレイト（Trait）の型システム上の位置付け（Trait は型・インターフェースではないため `instanceof` の対象にできない仕様）。
- デザインパターン「ステートパターン（State Pattern）」の Enum によるリファクタリング。
- 不正な状態遷移を防御するドメインロジックのカプセル化（Tell, Don't Ask 原則）。

### 2. 実施したテスト・検証概要
- ステートマシンの正常遷移フロー:
  - `OrderStatus::Draft -> Paid -> Shipped` の順序で正しく遷移し、各ステップで戻り値の Enum Case が期待通り更新されることを検証。
- 不正遷移に対する例外防御:
  - `Draft` からいきなり `Shipped` へ遷移しようとすると、`canTransitionTo()` で却下され `LogicException` がスローされることを実証。
  - 終了状態（`Cancelled`）から他の状態へ変更しようとしても、厳密に例外でブロックされることをアサーション。
- インターフェースとトレイトの統合:
  - `OrderStatus::Draft instanceof HasLabelInterface` が `true` となること。
  - トレイトに定義した共通メソッド `describe()` が各 Case の `$this->name` を用いて `"Status: Draft"` を正しく返すことを検証。

### 3. 直面した落とし穴・内部挙動の気付き
- **Trait は `instanceof` や `toBeInstanceOf` の対象にできない**:
  - トレイトは PHP の言語仕様上、「コードの水平方向の再利用（コンパイル時展開）」のための機構であり、型（クラスやインターフェース）ではない。
  - したがって `expect($status)->toBeInstanceOf(SomeTrait::class)` を呼ぶと `UnknownClassOrInterfaceException`（または致命的エラー）となる。トレイトの振る舞い検証はメソッド呼び出し、または `class_uses()` リフレクションで行う必要がある。
- **プロパティが禁止されているからこそステートマシンが綺麗に書ける**:
  - Enum が可変プロパティを持てない制約のおかげで、状態遷移メソッドは必然的に「自分自身をミューテートする」のではなく、**「遷移先の新しい Enum Case を返す（イミュータブルな関数型アプローチ）」** という安全な設計に強制される。

### 4. 実務・Qiita 向けのアウトプット要点
- 古典的なオブジェクト指向では、状態遷移を管理するために「State 基底クラス」と状態ごとの具象クラス（`DraftState`, `PaidState`, `ShippedState`...）を大量に作る必要があり、クラス設計が肥大化しがちだった。
- PHP 8.1 の Enum を使えば、**1つの Enum ファイルの中に「許可された状態一覧」「遷移可能ルール」「各状態の振る舞い（ラベルや色、ログ出力）」を完全に凝縮** できる。保守性と可読性が飛躍的に向上する、モダン PHP アーキテクチャの必須パターンである。
