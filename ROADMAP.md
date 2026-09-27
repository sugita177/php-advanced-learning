# 学習ロードマップ (ROADMAP.md)

PHP 8のコア領域（zval, 型のジャグリング, SPL, オブジェクトモデル）を深掘りしつつ、PHP 8.5 の最新仕様・第一級 Callable・関数型アプローチまでを体系的に習得するロードマップです。

---

## 全体構成

| フェーズ | テーマ | 主な学習・検証内容 |
| :--- | :--- | :--- |
| **Phase 1** | **型システムと評価・演算子の深層** | 型のジャグリング、厳密比較 vs 緩やかな比較、ビット演算、演算子優先順位、Match式、DNF型 (Disjunctive Normal Form) |
| **Phase 2** | **メモリモデルと変数のライフサイクル** | zval 構造、Copy on Write (COW)、リファレンス (`&`) の実態、循環参照とGC (Garbage Collector)、弱参照 (WeakReference / WeakMap) |
| **Phase 3** | **オブジェクト指向とメタプログラミング** | マジックメソッドの呼び出し境界、遅延静的結合 (`static::` vs `self::`)、無名クラス、readonly クラス・プロパティ、Attributes (属性) |
| **Phase 4** | **SPL (Standard PHP Library) と反復処理** | `Generator` / `yield`、`ArrayAccess` / `Countable`、SPL データ構造 (`SplFixedArray`, `SplDoublyLinkedList` など)、イテレータのネスト合成 |
| **Phase 5** | **モダン関数型PHPと合成・カリー化** | 第一級 Callable (`strlen(...)`)、クロージャ束縛 (`Closure::bind`)、高階関数、関数合成 (パイプライン処理)、例外 vs Result型アプローチ |
| **Phase 6** | **エラーハンドリングと並行・非同期基盤** | `Error` vs `Exception`、例外伝播とスタックトレース、Fiber によるコルーチン基礎、内部リソース管理 |

---

## 進捗チェックリスト

### Phase 1: 型システムと評価・演算子の深層
- [x] **Lesson 1.1**: 緩やかな比較 (`==`) の PHP 8 における刷新と型のジャグリング境界
- [x] **Lesson 1.2**: 宇宙船演算子 (`<=>`) と複合ソートアルゴリズム
- [x] **Lesson 1.3**: ビット演算・論理演算子の短絡評価と優先順位の罠
- [ ] **Lesson 1.4**: `match` 式の厳密一致 (`===`) と網羅性検査 (Exhaustiveness Check)
- [ ] **Lesson 1.5**: 交差型 (Intersection Types) と DNF型 (Disjunctive Normal Form) の型境界

### Phase 2: メモリモデルと変数のライフサイクル
- [ ] **Lesson 2.1**: 配列と変数の Copy on Write (COW) 挙動の検証
- [ ] **Lesson 2.2**: 参照渡し (`&`) が COW に与える影響と参照の分離
- [ ] **Lesson 2.3**: 循環参照と `gc_collect_cycles()` の内部挙動
- [ ] **Lesson 2.4**: `WeakReference` / `WeakMap` によるメモリリーク抑止

### Phase 3: オブジェクト指向とメタプログラミング
- [ ] **Lesson 3.1**: `__get` / `__set` / `__call` / `__callStatic` の発火タイミングとオーバーヘッド
- [ ] **Lesson 3.2**: 遅延静的結合 (`static::` / `self::` / `parent::`) の内部探索順序
- [ ] **Lesson 3.3**: `readonly` クラス / プロパティの不変性とリフレクションによる破壊検証
- [ ] **Lesson 3.4**: Attributes (属性) を用いた宣言的メタプログラミング

### Phase 4: SPL と反復処理
- [ ] **Lesson 4.1**: `Generator` (`yield`, `yield from`) の双方向通信 (`send()`, `throw()`)
- [ ] **Lesson 4.2**: SPL データ構造 vs 組み込み配列のメモリ・実行速度特性
- [ ] **Lesson 4.3**: `IteratorIterator`, `FilterIterator`, `LimitIterator` によるストリームパイプライン

### Phase 5: モダン関数型PHPと合成・カリー化
- [ ] **Lesson 5.1**: 第一級 Callable 構文 (`callable(...)`) と無名関数の最適化
- [ ] **Lesson 5.2**: `Closure::bind` / `Closure::fromCallable` による動的スコープ操作
- [ ] **Lesson 5.3**: カリー化 (Currying) と部分適用 (Partial Application) の実装
- [ ] **Lesson 5.4**: パイプライン演算子代替の関数合成 (`compose`, `pipe`)

### Phase 6: エラーハンドリングと並行・非同期基盤
- [ ] **Lesson 6.1**: `Throwable` 階層構造と `try-catch-finally` 内の制御フロー
- [ ] **Lesson 6.2**: カスタムエラーハンドラと `ErrorException` への変換
- [ ] **Lesson 6.3**: Fiber を使った協調的マルチタスクとスケジューラの実装
