# Phase 5: モダン関数型PHPと合成・カリー化

---

## Lesson 5.1: 第一級 Callable 構文 (`callable(...)`) と無名関数の最適化

- **検証日**: 2026-10-03
- **検証コード**:
  - 実証スクリプト: `src/Phase5/Lesson5_1_FirstClassCallable.php`
  - Pestテスト: `tests/Unit/Phase5/Lesson5_1_FirstClassCallableTest.php`

### 1. 検証した言語仕様・テーマ
- PHP 8.1 で導入された第一級 Callable 構文（`First-class Callable Syntax`: `func(...)`, `$obj->method(...)`）。
- 生成される具象オブジェクトの型（疑似型 `callable` ではなく具象クラス `Closure` への自動変換）。
- 従来の文字列指定（`'func'`）や配列指定（`[$obj, 'method']`）と比較した静的解析・早期エラー検知の優位性。
- プライベートメソッドに対するスコープカプセル化（`$this->privateMethod(...)` が保持するアクセス権）。

### 2. 実施したテスト・検証概要
- 具象クラスと実行の検証:
  - `$myStrLen = strlen(...)` が `Closure::class` のインスタンスであること。
  - `$myStrLen('hello') === 5` で実行可能であること。
  - `array_map(strtoupper(...), ['php', 'pest'])` で高階関数へシームレスに渡せること。
- 未定義関数の早期エラー検知:
  - 存在しない関数 `undefined(...)` を評価しようとした瞬間、呼び出しを待たずに即座に `Error: Call to undefined function` がスローされること。
- プライベートスコープのカプセル化:
  - クラス外部から `$service->decrypt('hoge')` を直接呼ぶと `Error`（Call to private method）となる。
  - しかしクラス内部から生成した `$service->getDecryptor()`（`$this->decrypt(...)`）を実行すると、private スコープと `$this` がバインドされたまま外部から実行可能であること。

### 3. 直面した落とし穴・内部挙動の気付き
- **第一級 Callable が返すオブジェクトの正体**:
  - `callable` は型宣言のための疑似型であり、第一級 Callable 構文が返すのは常に標準クラス `Closure` のインスタンスである。これにより、従来の無名関数と全く同一の API（`call()`, `bindTo()` など）をシームレスに適用できる。
- **バグの早期検知（Fail Fast）**:
  - 文字列や配列によるコールバック指定は、実際に呼び出されるその瞬間までタイポや存在チェックが行われない（遅延エラー）。一方、第一級 Callable 構文は構文評価の時点で即座に関数・メソッドの存在検査が走るため、潜在バグの早期検知と静的解析（PHPStan / IDE 補完）に絶大な効果を持つ。

### 4. 実務・Qiita 向けのアウトプット要点
- PHP 8.1 以降のコードベースでは、コールバックを渡す際に従来の文字列（`'trim'`）や配列（`[$this, 'handler']`）を使うのを完全に廃止し、すべて第一級 Callable 構文（`trim(...)`, `$this->handler(...)`）に統一すべき。
- クラス内部のプライベートな特定ロジックのみを安全に切り出して外部（DI コンテナや別サービス）に渡したい場合、パブリックメソッドを新たに設ける代わりに `$this->privateMethod(...)` の `Closure` を返すことで、オブジェクトの余計な公開インターフェースを増やさずにカプセル化を保てる。
