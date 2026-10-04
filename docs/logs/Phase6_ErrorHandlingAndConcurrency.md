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
