# Phase 6: エラーハンドリングと並行・非同期基盤

---

## Lesson 6.1: `Throwable` 階層構造と `try-catch-finally` 内の制御フロー

- **検証日**: 2026-10-04
- **検証コード**:
  - 実証スクリプト: `src/Phase6/Lesson6_1_ThrowableAndFinally.php`
  - Pestテスト: `tests/Unit/Phase6/Lesson6_1_ThrowableAndFinallyTest.php`

### 1. 検証した言語仕様・テーマ
- `finally` ブロックの絶対的実行保証と割り込み制御フロー。
- `finally` 内で `return` を実行した際の戻り値上書き（try ブロックの `return` の無効化）。
- `finally` 内の `return` による未捕捉例外（Unhandled Exception）の揉み消し（Swallowed Exception）仕様。
- `finally` 内で新たな例外をスローした際の、元の例外情報の喪失。
- PHP 7.0 の `Throwable` インターフェース階層（`Exception` vs `Error`）と、PHP 7.1 の multi-catch 構文（`catch (TypeA | TypeB $e)`）。

### 2. 実施したテスト・検証概要
- `finally` 内の `return` による値上書き:
  - `try { return 'TRY'; } finally { return 'FINALLY'; }` の戻り値が `'FINALLY'` となることを実証。
- 未捕捉例外の揉み消し検証:
  - `try` 内で `throw new RuntimeException()` を投げ、`catch` されなかった場合でも、`finally` 内で `return 'CLEANUP'` すると例外が消滅して正常終了することをアサーション。
- `Throwable` 階層と multi-catch:
  - `TypeError`（`Error` 傘下）と `InvalidArgumentException`（`Exception` 傘下）を `catch (TypeError | InvalidArgumentException $e)` で同時に捕捉できること。
  - 想定外の例外は基底の `catch (Throwable $e)` にフォールバックすること。

### 3. 直面した落とし穴・内部挙動の気付き
- **`finally` 内の `return` は重大なアンチパターン**:
  - Zend Engine は関数脱出時に `finally` を確実に実行する。その中で `return` が呼ばれると、それ以前に設定されていた戻り値だけでなく、**スタック上を伝播中だった未処理の例外すら破棄して正常終了** させてしまう。
  - バグや障害の検出が完全に阻害されるため、PHPStan などの静的解析では禁止事項とされている。
- **`finally` 内での例外発生と元例外の蒸発**:
  - `try` ブロックで例外 A が飛び、`finally` ブロック内で別の例外 B が飛んだ場合、PHP は例外 B のみを呼び出し元へ報告し、例外 A の情報は消滅する（Java のような Suppressed Exception は PHP には存在しない）。

### 4. 実務・Qiita 向けのアウトプット要点
- `finally` は、ファイルポインタのクローズ（`fclose`）、DB ロックの解放、一時ファイルの削除など、「後始末（Cleanup）」のみに専念させ、絶対に `return` や `throw` を記述してはならない。
- エラーハンドリングの基底型には、古い PHP 5 の癖で `Exception` を書いてしまいがちだが、PHP 7 以降は `Error`（型エラーやアサーションエラー）もキャッチできるように必ず `Throwable` を指定する。

---

## Lesson 6.2: カスタムエラーハンドラと `ErrorException` への変換

- **検証日**: 2026-10-05
- **検証コード**:
  - 実証スクリプト: `src/Phase6/Lesson6_2_ErrorHandlerAndErrorException.php`
  - Pestテスト: `tests/Unit/Phase6/Lesson6_2_ErrorHandlerAndErrorExceptionTest.php`

### 1. 検証した言語仕様・テーマ
- PHP 伝統の「エラー（Warning, Notice, Deprecated）」と「例外（Exception, Error）」の分離構造。
- 通常の `try-catch` では Warning や Notice を捕捉できず素通りしてしまう仕様。
- `set_error_handler` によるエラーの `ErrorException` への昇格と `try-catch` 一元管理。
- エラー抑制演算子 `@` の PHP 8 におけるビットマスク仕様（`error_reporting() & $severity` の判定）。
- エラーハンドラの戻り値（`true` を返すと PHP 標準ハンドラやテストランナーのリスナーを抑止して完全消費する仕様）。
- `restore_error_handler()` によるスタック構造とクリーンアップ。

### 2. 実施したテスト・検証概要
- 通常 try-catch の限界:
  - `trigger_error('My warning', E_USER_WARNING)` が `try-catch (Throwable $e)` を素通りし、`$caught` が `false` のままであることを検証。
  - テストランナーである Pest に Warning（`!`）が届くこと自体が、try-catch が一切エラーを捕捉できずに外側へ漏れ出たことの決定的な証拠であることを確認。
- `ErrorException` への昇格:
  - カスタムエラーハンドラ内で `throw new ErrorException(...)` を実行することで、Warning が即座に例外として catch できることを検証。
- エラー抑制演算子 `@` の尊重:
  - ハンドラ内で `if (! (error_reporting() & $severity)) return false;` を実装。
  - 通常の `trigger_error` は `ErrorException` として例外化されるが、`@trigger_error` の場合は例外が投げられずにスルーされることをアサーション。

### 3. 直面した落とし穴・内部挙動の気付き（ディスカッションの記録）
- **テストランナーの Warning 表示は「テスト失敗」ではなく「仕様の完全な証明」**:
  - `try-catch does not catch warning` のテストで Pest が `! Warning` を通知するのは、PHP の `try-catch` が Warning を捕まえられずに外側へ素通りさせたからこそ発生する。
  - 安易に Warning 表示を消すために `set_error_handler(fn() => true)` などのダミーハンドラを置いてしまうと、「PHP の素の try-catch の限界」ではなく「自作ハンドラで握りつぶした結果」をテストすることになり、テストの趣旨が破綻する。Warning がテストランナーまで漏れ出ることこそが、言語仕様の正しい実証である。
- **Pest 実行環境における `error_reporting` の初期値トラップ**:
  - Pest（PHPUnit）の内部では、テスト実行中に `error_reporting` が `245`（`E_USER_WARNING` を除外したビットマスク）にセットされていた。
  - そのため `@` を付けていなくても `error_reporting() & $severity` が最初から `0`（false）になり、ハンドラが「@ で抑制されている」と誤認してしまう罠があった。テスト内で `error_reporting(E_ALL)` を明示指定することで正確なビット判定を実現した。

### 4. 実務・Qiita 向けのアウトプット要点
- Laravel や Symfony などのモダンフレームワークの基盤では、すべての PHP エラー（Notice, Warning）を `set_error_handler` で `ErrorException` に変換して例外機構に統合している。
- レガシーコードをモダン化する際、安易に `@` でエラーを握りつぶすのではなく、このカスタムエラーハンドラを導入してすべての警告を例外として顕在化させることで、潜在バグを徹底的に炙り出すことができる。

---

## Lesson 6.3: Fiber を使った協調的マルチタスクとスケジューラの実装

- **検証日**: 2026-10-05
- **検証コード**:
  - 実証スクリプト: `src/Phase6/Lesson6_3_FiberConcurrency.php`
  - Pestテスト: `tests/Unit/Phase6/Lesson6_3_FiberConcurrencyTest.php`

### 1. 検証した言語仕様・テーマ
- PHP 8.1 で導入された低レベル並行プリミティブ `Fiber`。
- スタックフル（Stackful）コルーチンとジェネレータ（Stackless）の決定的な相違。
- Fiber のライフサイクルメソッドと状態管理（`start()`, `suspend()`, `resume()`, `getReturn()`, `isStarted()`, `isSuspended()`, `isTerminated()`）。
- 協調的マルチタスク（Cooperative Multitasking）とシングルスレッド並行処理（Concurrency）。
- ラウンドロビン方式のミニ・イベントループスケジューラの実装。

### 2. 実施したテスト・検証概要
- Fiber の完全なライフサイクルと双方向データ通信:
  - `start('Alice')` で Fiber を起動し、第 1 引数が Fiber 内部のクロージャへ渡ることを確認。
  - Fiber 内部で `Fiber::suspend('PAUSED_1')` を呼ぶと、即座に中断され、`start()` の戻り値として `'PAUSED_1'` が得られることを検証。
  - 呼び出し元で `$fiber->resume('RESUMED_DATA')` を呼ぶと、中断していた `Fiber::suspend()` の戻り値としてデータが注入されて再開することを検証。
  - 実行完了時、`resume()` の戻り値は `null` となり、Fiber 自体の最終的な return 値は `$fiber->getReturn()` で取得することを検証。
- スタックフル（Stackful）コルーチンの真価:
  - `methodA() -> methodB() -> methodC()` と多段に深くネストされた関数呼び出しの最深部で `Fiber::suspend()` を実行。
  - ジェネレータ（`yield`）のように呼び出し経路上のすべての関数を `Generator` に書き換える「関数の色付け問題（Function Coloring Problem）」を起こすことなく、コールスタック全体ごと中断・復帰できることを実証。
- ラウンドロビンスケジューラの実装:
  - 複数タスク（Task1: 3ステップ、Task2: 2ステップ）を Fiber 化してキューで管理。
  - `while (!empty($queue))` によるイベントループを回し、各タスクが協調的に CPU を譲り合いながら交互に実行され、Task1-Step1 → Task2-Step1 → Task1-Step2 → Task2-Step2 → Task1-Step3 の順序で完了することを検証。

### 3. 直面した落とし穴・内部挙動の気付き
- **Fiber は「並列（Parallelism）」ではなく「並行（Concurrency）」**:
  - Fiber はマルチスレッドやマルチプロセスではない。OS スレッドは 1 つのまま、PHP 実行コンテキスト（Cスタック / Zend 仮想マシンスタック）のポインタを切り替えているだけ。
  - したがって、CPU 負荷の高い重いループを Fiber に渡しても並列処理で速くなるわけではない。真価を発揮するのは「I/O 待ち（ネットワーク通信、DB クエリ、ファイルアクセス）」で待たされている間に別の Fiber に CPU を譲るノンブロッキング処理。
- **ジェネレータとの決定的な違い（Stackful vs Stackless）**:
  - `Generator` は「スタックレス」であり、`yield` を書いたそのスコープしか中断できない。深い関数から抜けるにはすべての階層で `yield` をバケツリレーしなければならない。
  - `Fiber` は「スタックフル」であり、Zend Engine が C レベルでスタックコンテキストを退避・復元するため、どれほど深い呼び出し先・サードパーティライブラリの内部からでも直接中断・再開できる。

### 4. 実務・Qiita 向けのアウトプット要点
- PHP 8.1 の Fiber は、エンドユーザーが直接書くための API ではなく、フレームワークや非同期ランタイム（Revolt, ReactPHP, Amphp, Swoole）の作者がイベントループを構築するための「低レベル基盤プリミティブ」である。
- Revolt などのイベントループと組み合わせることで、JavaScript の `async/await` や Go の Goroutine のように、「見た目は完全な同期コード（ブロッキングコード）のように書けるのに、内部では非同期ノンブロッキングで並行実行される」という世界が PHP 8 で実現された。

