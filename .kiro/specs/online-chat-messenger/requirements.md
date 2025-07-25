# 要件定義書

## 概要

オンラインチャットメッセンジャーは、リアルタイムでのグループ交流を可能にするクライアントサーバ型のアプリケーションです。このプロジェクトは3つのステージに分かれており、基本的なUDPベースのチャット機能から始まり、チャットルーム機能、そして高度な暗号化とGUIクライアントまでを段階的に実装します。学習目的として、サーバ操作の基本、ネットワークプログラミング、そしてバックエンド開発のスキル向上を目指します。

## 要件

### 要件1: 基本的なサーバ機能

**ユーザーストーリー:** システム管理者として、チャットサービスを提供するためのサーバを起動し、クライアントからの接続を待ち受けたい

#### 受入基準

1. WHEN サーバがCLIで起動される THEN システムはバックグラウンドで着信接続を待ち受ける状態になる SHALL
2. WHEN サーバがオフラインの場合 THEN チャットサービス全体が停止状態になる SHALL
3. WHEN サーバが起動している THEN UDPネットワークソケットを使用してクライアントとの通信を行う SHALL

### 要件2: クライアント接続とメッセージング

**ユーザーストーリー:** ユーザーとして、CLIクライアントを使用してサーバに接続し、他のユーザーとリアルタイムでメッセージを交換したい

#### 受入基準

1. WHEN クライアントがサーバに接続する THEN ユーザーにユーザー名の入力を求める SHALL
2. WHEN メッセージが送信される THEN サーバとクライアントは最大4096バイトのメッセージを処理する SHALL
3. WHEN クライアントがメッセージを送信する THEN 接続中の全てのクライアントにメッセージがリレーされる SHALL
4. WHEN メッセージが送信される THEN UTF-8エンコーディングを使用してデータを処理する SHALL

### 要件3: メッセージプロトコル

**ユーザーストーリー:** 開発者として、一貫したメッセージフォーマットを使用してクライアントとサーバ間の通信を行いたい

#### 受入基準

1. WHEN メッセージが送信される THEN 最初の1バイトはユーザー名の長さ（usernamelen）を示す SHALL
2. WHEN usernamelen が設定される THEN 最大255バイト（2^8 - 1）までのユーザー名をサポートする SHALL
3. WHEN メッセージが構成される THEN usernamelen バイト後に実際のメッセージ内容が続く SHALL
4. WHEN データが処理される THEN UTF-8でエンコード・デコードされる SHALL

### 要件4: クライアント管理システム

**ユーザーストーリー:** サーバ管理者として、接続中のクライアントを追跡し、非アクティブなクライアントを自動的に削除したい

#### 受入基準

1. WHEN サーバが稼働している THEN 現在接続中の全クライアント情報をメモリ上に保存する SHALL
2. WHEN クライアントが連続で失敗する THEN 自動的にリレーシステムから削除される SHALL
3. WHEN クライアントがしばらくメッセージを送信しない THEN 自動的にリレーシステムから削除される SHALL
4. WHEN UDPコネクションレス通信を使用する THEN 各クライアントの最後のメッセージ送信時刻を追跡する SHALL

### 要件5: チャットルーム機能（ステージ2）

**ユーザーストーリー:** ユーザーとして、自分専用のチャットルームを作成し、他のユーザーを招待してプライベートな会話を行いたい

#### 受入基準

1. WHEN ユーザーがチャットルームを作成する THEN TCPプロトコルを使用してサーバとの信頼性の高い通信を行う SHALL
2. WHEN チャットルーム作成が完了する THEN サーバは一意のクライアントトークンを生成してクライアントに送信する SHALL
3. WHEN クライアントがチャットルームに参加する THEN 有効なトークンとIPアドレスの一致が必要である SHALL
4. WHEN ホストが退出する THEN チャットルームは自動的に閉じられる SHALL

### 要件6: TCPプロトコル（TCRP）

**ユーザーストーリー:** 開発者として、チャットルームの作成と参加のための標準化されたプロトコルを使用したい

#### 受入基準

1. WHEN TCRPメッセージが送信される THEN 32バイトのヘッダー（RoomNameSize, Operation, State, OperationPayloadSize）を含む SHALL
2. WHEN ルーム名が設定される THEN 最大28バイトまでのUTF-8エンコードされた名前をサポートする SHALL
3. WHEN 新しいチャットルームを作成する THEN 操作コード1を使用する SHALL
4. WHEN チャットルームに参加する THEN 操作コード2を使用する SHALL

### 要件7: 高度な機能（ステージ3）

**ユーザーストーリー:** ユーザーとして、パスワード保護されたチャットルーム、デスクトップGUI、暗号化通信を利用したい

#### 受入基準

1. WHEN チャットルームが作成される THEN オプションでパスワード保護を設定できる SHALL
2. WHEN デスクトップクライアントが提供される THEN Electron.jsを使用したGUIアプリケーションを提供する SHALL
3. WHEN メッセージが送信される THEN RSA類似の暗号化方式を使用してメッセージを保護する SHALL
4. WHEN 暗号化が実装される THEN クライアントとサーバ間で公開鍵・秘密鍵の交換を行う SHALL

### 要件8: パフォーマンス要件

**ユーザーストーリー:** システム管理者として、大量のユーザーとメッセージを処理できる高性能なシステムを運用したい

#### 受入基準

1. WHEN システムが稼働している THEN 毎秒最低10,000パケットの送信をサポートする SHALL
2. WHEN 1000人が同一チャットルームにいる THEN 毎秒最低10メッセージを処理できる SHALL
3. WHEN 500のチャットルームが存在する THEN 各ルームで10人が毎秒平均2メッセージを送信する状況をサポートする SHALL
4. WHEN リアルタイム通信が行われる THEN データの即時性を信頼性よりも優先する SHALL

### 要件9: 拡張性考慮事項

**ユーザーストーリー:** アーキテクトとして、将来的な大規模展開に備えた拡張可能な設計を検討したい

#### 受入基準

1. WHEN 大規模展開を考慮する THEN ロードバランシングによる複数サーバへのトラフィック分散を検討する SHALL
2. WHEN マルチコア環境で実行される THEN 並行処理による性能向上を検討する SHALL
3. WHEN 分散環境で実行される THEN 複数マシンでのタスク分散処理を検討する SHALL
4. WHEN 数億ユーザーの規模を想定する THEN 適切なスケーリング戦略を検討する SHALL
