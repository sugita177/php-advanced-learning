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

---

## Lesson 7.2: ストリームフィルタ (`php_user_filter`) によるオンザフライデータ変換

- **検証日**: 2026-10-06
- **検証コード**:
  - 実証スクリプト: `src/Phase7/Lesson7_2_StreamFilter.php`
  - Pestテスト: `tests/Unit/Phase7/Lesson7_2_StreamFilterTest.php`

### 1. 検証した言語仕様・テーマ
- ストリームを通過するデータ（読み書き）をインラインで変換するストリームフィルター機構。
- 組み込みフィルター（`string.toupper`, `convert.base64-encode` 等）と `STREAM_FILTER_WRITE` / `STREAM_FILTER_READ` の適用境界。
- メタラッパー構文 `php://filter/read=.../resource=...` による高レベル API でのオンザフライ変換。
- カスタムフィルタークラスの実装仕様: 基底クラス `php_user_filter` の継承必須性（ストリームラッパーとの対比）。
- バケットブリゲード（Bucket Brigade）アーキテクチャ: `$in` / `$out`、`stream_bucket_make_writeable`、`stream_bucket_append`。
- フィルターのステータス定数 `PSFS_PASS_ON`（次へ渡す）、`PSFS_FEED_ME`（データ要求）、`PSFS_ERR_FATAL`。

### 2. 実施したテスト・検証概要
- 組み込みフィルターの検証:
  - `php://memory` に `stream_filter_append($fp, 'string.toupper', STREAM_FILTER_WRITE)` を適用。
  - 小文字で書き込んだデータが、内部で即座に大文字化されてストリームに格納されることを実証。
- カスタムフィルターによる機密データマスキング:
  - `MaskSecretFilter extends php_user_filter` を実装し、`stream_filter_register('mask.secret', ...)` で登録。
  - 書き込み時にバケットを取り出して `str_replace` で `"SECRET"` を `"********"` に置換し、消費バイト数 `$consumed` を加算して `$out` へ流すパイプラインを構築。
  - `fwrite($fp, 'User: Alice, Key: SECRET')` を行い、読み出し時に `User: Alice, Key: ********` と置換されていることを検証。

### 3. 直面した落とし穴・内部挙動の気付き
- **ラッパーとフィルターの設計哲学の相違**:
  - ストリームラッパー（`stream_wrapper_register`）は、規定メソッドさえあればどんなクラスでも良い「Duck Typing（基底クラスなし）」であるのに対し、ストリームフィルター（`stream_filter_register`）は、**必ず組み込み基底クラス `php_user_filter` を `extends` しなければならない** という明確な言語仕様の差異が存在する。
- **バケットブリゲード（Bucket Brigade）モデルの理解**:
  - フィルターに渡されるデータは単なる文字列（string）ではなく、C言語レベルのバケツリレー構造体（`StreamBucket` オブジェクト）である。
  - `stream_bucket_make_writeable($in)` で入力キューからバケットを取り出し、`$bucket->data` を書き換えて、`stream_bucket_append($out, $bucket)` で出力キューへ送り出す。この低レベルな構造を理解していないと、独自フィルターは作成できない。
- **定数名 `PSFS_` の由来**:
  - 戻り値定数の `PSFS_PASS_ON` は **P**HP **S**tream **F**ilter **S**tatus の略であり、データ処理が完了して「次のフィルターへデータをパスした（Pass On）」ことを意味する。

### 4. 実務・Qiita 向けのアウトプット要点
- メタラッパー `php://filter` は、巨大な CSV ファイルやログをメモリに一括ロードすることなく、読み出しながらストリーミングで gzip 解凍や文字コード変換（`convert.iconv.*`）を行う際、メモリ枯渇を防ぐ決定的な武器になる。
- 独自フィルターを使えば、ログ出力ストリームに機密情報（クレジットカード番号やパスワード）が紛れ込むのを、アプリケーションレイヤーではなくストリームレイヤーで自動的・透過的にマスキングする防御策を構築できる。
