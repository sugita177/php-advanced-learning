# Phase 7: ストリームと入出力の抽象化 (Streams & Filters)

---

## Lesson 7.1: ストリームラッパー (`stream_wrapper_register`) による仮想ファイルシステムの構築

- **検証日**: 2026-10-05
- **検証コード**:
  - 実証スクリプト: `src/Phase7/Lesson7_1_StreamWrapper.php`
  - Pestテスト: `tests/Unit/Phase7/Lesson7_1_StreamWrapperTest.php`

### 1. 検証した言語仕様・テーマ
- PHP のすべての I/O（`file_get_contents`, `fopen`, `unlink` 等）を抽象化する Streams アーキテクチャ。
- `stream_wrapper_register('protocol', ClassName::class)` によるカスタムプロトコルの登録と、`stream_wrapper_unregister('protocol')` による解除。
- ストリームラッパーがインターフェースを強制しない（Duck Typing）仕様と、PHP が要求するメソッド仕様。
- ストリーム操作系メソッド（`stream_*`）とファイルシステム操作系メソッド（`unlink`, `rename`, `mkdir` 等）の命名規則の決定的な差異。
- インメモリ仮想ファイルシステム（VFS）の実装と、シーク（`fseek`）・追記・複数回 `fwrite` の挙動。

### 2. 実施したテスト・検証概要
- 仮想ストレージへの読み書き（高レベル API）:
  - `file_put_contents('vfs://test.txt', 'hello, stream!')` で書き込み、`file_get_contents` で完全一致するデータを読み出せることを検証。
- 存在しないファイルの読み込みと Warning 発行:
  - 未登録のパスを `file_get_contents` した場合、`stream_open` が `false` を返し、PHP コアが `E_WARNING` を発行しつつ戻り値が `false` になることを検証。
- 複数回書き込み（低レベル API）とファイル削除:
  - `fopen('vfs://stream.txt', 'w')` で開き、`fwrite` を複数回呼び出した際に、データが丸ごと上書きされずに `substr_replace` / `$position` の進行によって正しく連結（`'Hello, world!'`）されることをアサーション。
  - `unlink('vfs://stream.txt')` を呼ぶことで、ラッパー内の `unlink()` メソッドが発火し、ストレージからファイルが正しく消去されることを実証。

### 3. 直面した落とし穴・内部挙動の気付き
- **メソッド名のひっかけ（`stream_unlink` ではなく `unlink`）**:
  - `stream_open`, `stream_read`, `stream_write` などのストリーム操作は `stream_` プレフィックスが付くが、ファイルパスに対する操作（`unlink`, `rename`, `mkdir`, `rmdir`）はプレフィックスなしの関数名そのまま定義する必要がある。上級試験頻出の落とし穴。
- **Pest (PHPUnit) における Warning の通知理由**:
  - `file_get_contents` に `@` をつけても Pest が Warning を検知するのは、PHP 8 では `@` をつけてもエラーハンドラ自体は呼び出されるため。Pest が「テスト内で Warning が発生した事実」を補足・報告しているだけであり、テスト自体はグリーン。
- **追記・シークにおける内部ポインタ管理**:
  - 単純な `$storage[$path] = $data` では複数回 `fwrite` でデータが消失する。`$this->position` と `strlen($data)` の管理が不可欠。

### 4. 実務・Qiita 向けのアウトプット要点
- テスト時に実ディスクを汚さずにファイル I/O をモック化したい場合、`mikey179/vfsstream` などの有名ライブラリが使われるが、その内部はまさにこの `stream_wrapper_register` で実現されている。
- S3 などのクラウドストレージ（AWS SDK の `s3://` ラッパー）も同じ仕組みであり、PHP 標準の `file_get_contents('s3://bucket/key')` で透過的にクラウドファイルにアクセスできる基盤となっている。
