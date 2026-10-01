# Phase 3: オブジェクト指向とメタプログラミング

---

## Lesson 3.1: マジックメソッドの発火境界とオーバーヘッド

- **検証日**: 2026-09-30
- **検証コード**:
  - 実証スクリプト: `src/Phase3/Lesson3_1_MagicMethods.php`
  - Pestテスト: `tests/Unit/Phase3/Lesson3_1_MagicMethodsTest.php`

### 1. 検証した言語仕様・テーマ
- `__get` / `__set` の厳密な発火境界（「未定義」だけでなく呼び出し元スコープから「アクセス不能」な `private` / `protected` も対象）。
- `public` プロパティが存在する場合の直接メモリ読み取り（オペコード `ZEND_FETCH_OBJ_R`）とマジックメソッドのバイパス。
- `__set` と `__isset` の連動関係（動的プロパティに対して `__isset` を実装しないと `isset()` が常に `false` になる罠）。
- 直接プロパティアクセス vs マジックメソッド経由アクセスの内部動作の違いと、実行オーバーヘッド（約 7 〜 8 倍の速度差）。

### 2. 実施したテスト・検証概要
- 発火境界のテスト:
  - `public $publicName` へのアクセスでは `__get` が発火せず、直接 `'Alice'` が取得されること。
  - `private $secretKey` に対し、クラス外部からアクセスするとアクセス権限エラーにならず、`__get` が発火して `'Magic get: secretKey'` が返ること。
  - クラス内部のメソッドからは private にアクセス可能なため、`__get` を介さず直接 `'secret_123'` が取得できること。
  - 動的に代入したプロパティに対し、`__isset` 未実装時は `isset()` が `false` となり、`__isset` 実装クラスでは `true` になること。
- ベンチマーク検証 (100,000回ループ):
  - 直接アクセス $T_1$ (約 0.9 ms) に対し、マジックメソッド $T_2$ (約 7.5 ms) は **約 7.8 倍の実行時間** を要することを実測・アサーション。

### 3. 直面した落とし穴・内部挙動の気付き
- **「アクセス不能」の意味（上級試験の頻出罠）**:
  - `__get` の対象は未定義プロパティだけではない。外部から private プロパティを読もうとした際、即座に Fatal Error になるのではなく、`__get()` が定義されていればそちらにルーティングされる。
- **なぜマジックメソッドは 7 倍以上遅いのか（Zend VM の内部構造）**:
  - 直接アクセスは「ポインタ演算によるメモリ読み取り（インラインキャッシュ有効）」だけで完了する。
  - マジックメソッドはプロパティに見えても実態は「完全な関数呼び出し」。`zend_execute_data` スタックフレームの確保、引数渡し、VM のコンテキストスイッチ、関数実行、スタック破棄という膨大なオーバーヘッドが毎回発生するため。
- **`__set` を書いたら `__isset` と `__unset` はセットで書く**:
  - `isset()` は存在しないプロパティに対して `__get()` を呼んで判定してくれない。`__isset` がなければ無条件で `false` を返すため、必ず 3 兄弟（set/isset/unset）で実装する必要がある。

### 4. 実務・Qiita 向けのアウトプット要点
- Laravel の Eloquent モデルなど、マジックメソッドに依存した設計は開発効率を高める一方で、数万〜数十万件規模の大量データバッチ処理ではボトルネックとなる。
- 大量処理を行うユースケースでは、Eloquent モデルの代わりに純粋な配列、`stdClass`、またはプロパティが直接宣言された型付き DTO（Data Transfer Object）を活用する。

---

## Lesson 3.2: 遅延静的結合 (Late Static Bindings) の内部探索順序とフォワーディングコール

- **検証日**: 2026-10-01
- **検証コード**:
  - 実証スクリプト: `src/Phase3/Lesson3_2_LateStaticBindings.php`
  - Pestテスト: `tests/Unit/Phase3/Lesson3_2_LateStaticBindingsTest.php`

### 1. 検証した言語仕様・テーマ
- 早期静的結合 (`self::`) と遅延静的結合 (`static::`) の本質的違い（コンパイル時固定 vs 実行時 called class 解決）。
- フォワーディングコール（Forwarding Call: `parent::`, `self::`, `static::`）による呼び出し元クラス情報の転送仕様。
- ノン・フォワーディングコール（明示的クラス名指定 `Class::method()`）による called class の上書き。
- ファクトリメソッドパターンにおける `new static()`（ポリモーフィズム維持）vs `new self()`（親クラス固定）の設計差異。
- 抽象クラス（`abstract class`）内での `new self()` による即死トラップ（`Cannot instantiate abstract class`）。

### 2. 実施したテスト・検証概要
- 3層継承（`LsBaseModel` → `LsUser` → `LsAdminUser`）の静的解決:
  - `LsUser::testSelf()` → `'LsBaseModel'`（`self::` は定義元クラスを固定解決）。
  - `LsUser::testStatic()` → `'LsUser'`（`static::` は呼び出し元を動的解決）。
  - `LsAdminUser::testStatic()` → `'LsAdminUser'`。
  - `LsAdminUser::forwardParent()` → `'LsAdminUser'`（`parent::` は called class を保持して親へバトンタッチするため、孫クラス名が返る）。
- ファクトリメソッドの型検証:
  - `LsCustomer::create()` → `LsCustomer` インスタンス。
  - `LsSpecialCustomer::create()` → `LsSpecialCustomer` インスタンス（`new static()` によりサブクラスを生成）。
  - `LsSpecialCustomer::createBySelf()` → `LsCustomer` インスタンス（`new self()` により、コードが書かれた親クラスに固定されてしまい、サブクラスが反映されない不具合を立証）。

### 3. 直面した落とし穴・内部挙動の気付き
- **フォワーディングコールのメカニズム（上級試験の最頻出罠）**:
  - `parent::` を挟むと一見親クラスが解決されそうに見えるが、Zend Engine は「最初に誰が呼んだか」というコンテキスト（called class）を保持したまま転送する。そのため親クラス内の `static::` は呼び出し元の孫クラスを解決する。
- **抽象クラスと `new self()` の致命的バグ**:
  - `abstract class` の中で `new self()` を呼ぶと、具象サブクラスから呼んだとしても PHP は抽象クラス自身を直接インスタンス化しようとするため、即座に Fatal Error (`Cannot instantiate abstract class`) でクラッシュする。抽象基底クラスのファクトリメソッドでは `new static()` が言語仕様上必須となる。
- **テストスイート内のクラス名重複エラー**:
  - Pest は同一プロセスで全テストを逐次ロードするため、テストファイル間で同名のクラス（`class User`）をトップレベルに宣言すると `Cannot redeclare class` で衝突する。テスト専用クラスにはプレフィックスを付与するか、名前空間を分ける必要がある。

### 4. 実務・Qiita 向けのアウトプット要点
- フレームワークやライブラリの基底モデル（ActiveRecord や DDD の Entity）でファクトリメソッドを実装する際は、戻り値の型定義に PHP 8.0 の `static` 型（`public static function create(): static`）を用い、実装も `new static()` を徹底する。
- 継承先で絶対にオーバーライドさせたくない不変の内部ロジックには `self::`（または `private final`）を用い、サブクラスの文脈に応じた柔軟な拡張を許容する箇所には `static::` を使い分ける。

---

## Lesson 3.3: readonly クラス / プロパティの不変性とリフレクションによる破壊検証

- **検証日**: 2026-10-02
- **検証コード**:
  - 実証スクリプト: `src/Phase3/Lesson3_3_ReadonlyAndReflection.php`
  - Pestテスト: `tests/Unit/Phase3/Lesson3_3_ReadonlyAndReflectionTest.php`

### 1. 検証した言語仕様・テーマ
- PHP 8.1 の `readonly` プロパティおよび PHP 8.2 の `readonly class` による完全な不変性（Immutability）と動的プロパティの禁止。
- `ReflectionProperty::setValue()` による不変性破壊の試行と Zend Engine による拒絶（`Error` スロー）。
- 浅い不変性（Shallow Immutability）の限界（保持するオブジェクトの内部プロパティは変更可能）。
- 参照渡し（`&$user->name`）の禁止と即死トラップ。
- 型付きプロパティの「未初期化状態（Uninitialized）」と `isset()` / `empty()` による安全な判定。
- PHP 8.3 の Withers パターン（`__clone()` 内での `unset()` による再初期化の解禁）。

### 2. 実施したテスト・検証概要
- 不変性とリフレクション保護:
  - 通常のプロパティ再代入（`$user->name = 'Bob'`）が基底の `Error`（`Cannot modify readonly property`）でブロックされること。
  - `ReflectionProperty::setValue()` によるリフレクション経由の書き換えも同一の `Error` で完全にブロックされること。
- 浅い不変性（Shallow Immutability）の検証:
  - `readonly class RoCompany` の `$address` プロパティ自体の差し替えは `Error` となる。
  - しかし、`$company->address->city = 'Aichi'` のように内部オブジェクト自体のミュータブルなプロパティは変更可能であること（参照先が固定されているだけ）。
- 参照取得の禁止:
  - `$ref = &$user->name` の実行が `Error`（`Cannot acquire reference to readonly property`）となること。
- 未初期化（Uninitialized）と `isset()`:
  - 未初期化の `readonly` プロパティを直接読み取ると `Error`（`must not be accessed before initialization`）となる。
  - しかし `isset($draft->title)` は例外を投げず、安全に `false` を返すこと。
- PHP 8.3 の `__clone()` による再初期化:
  - `__clone()` 内で `unset($this->age)` を実行することで、Withers メソッド（`withAge()`）から複製された新インスタンスに対してのみ 1 回の再初期化が可能になること。
  - 元インスタンスの不変性が完全に保持されること。

### 3. 直面した落とし穴・内部挙動の気付き
- **スローされる例外は `TypeError` ではなく `Error`**:
  - `readonly` の代入違反や参照渡し違反は、型の不一致ではなくランタイムの言語仕様違反であるため、`TypeError` ではなく基底の `\Error` が直接スローされる。
- **PHP 7.4 以降の「未初期化（Uninitialized）」状態**:
  - 型付きプロパティは宣言しただけでは `null` にならず「未初期化」という特殊状態になる。直接読むと即死するが、`isset()` は安全装置として機能し、クラッシュせず `false` を返す。
- **PHP 8.3 における `readonly` 変更の厳密なスコープ制限**:
  - `readonly` の再代入が許されるのは **`__clone()` マジックメソッドの内部（`$this` に対して）限定**。
  - 外部の Withers メソッド（`withAge()` など）から直接 `$clone->age = $newAge;` と書くことはできず、`__clone()` 内で `unset($this->age)` して未初期化状態に戻してから外部で代入するのが RFC 公式の定石パターン。

### 4. 実務・Qiita 向けのアウトプット要点
- ドメイン駆動設計（DDD）の Value Object（値オブジェクト）や DTO を実装する際、PHP 8.2 の `readonly class` を使うことでリフレクションによる裏技すら許さない堅牢なイミュータビリティを担保できる。
- ただし、ネストされたオブジェクトがある場合はディープイミュータブルにならないため、子オブジェクトもすべて `readonly class` にするか、`__clone()` でディープコピーを行う設計が必要。
- イミュータブルオブジェクトの部分更新（Withers パターン）は、PHP 8.3 の `__clone()` 内での `unset()` を活用することで、言語仕様に則ったエレガントな記述が可能となる。

