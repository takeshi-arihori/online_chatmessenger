# Online Chat Messenger

リアルタイムチャットメッセンジャーシステム - PHP 8.3, UDP/TCP デュアルプロトコル, RSA暗号化対応

## 📋 プロジェクト概要

このプロジェクトは、高性能なリアルタイムチャットシステムを段階的に実装します。UDP/TCPデュアルプロトコルによる効率的な通信と、RSA暗号化による高セキュリティを特徴とします。

### 🎯 主要特徴

- **高性能**: 10,000+ packets/second 処理能力
- **スケーラブル**: 1,000+ 同時接続、500+ チャットルーム対応
- **セキュア**: RSA 2048-bit暗号化（ステージ3）
- **クロスプラットフォーム**: CLI・GUI両対応

## 🚀 開発ステージ

### ステージ1: 基本UDPチャット（実装中）
- [x] **タスク#1**: プロジェクト構造とコアインターフェース
- [x] **タスク#2**: UDPメッセージプロトコル実装
- [ ] **タスク#3**: 基本UDPサーバ実装
- [ ] **タスク#4**: CLIクライアント実装
- [ ] **タスク#5**: メッセージ送受信機能

### ステージ2: チャットルーム機能（予定）
- TCP/UDP デュアルプロトコルシステム
- トークンベース認証
- ルーム作成・参加機能
- ホスト権限管理

### ステージ3: 高度な機能（予定）
- RSA暗号化通信
- パスワード保護ルーム
- Electron.js GUIクライアント
- 大規模スケーリング対応

## 🛠️ 技術スタック

- **言語**: PHP 8.3
- **ネットワーク**: Socket API (UDP/TCP)
- **非同期処理**: ReactPHP / Swoole / pcntl_fork
- **暗号化**: OpenSSL (RSA, 2048-bit)
- **テスト**: PHPUnit
- **GUI**: Electron.js + Node.js（ステージ3）
- **パッケージ管理**: Composer

## 📦 インストール

### 必要要件

- PHP 8.3+
- 拡張機能: `sockets`, `openssl`, `mbstring`
- Composer

### セットアップ

```bash
# リポジトリクローン
git clone https://github.com/takeshi-arihori/online_chatmessenger.git
cd online_chatmessenger

# 依存関係インストール
composer install

# テスト実行
composer test

# コード品質チェック
composer cs:check
composer analyse
```

## 🎮 使用方法

### 現在の実装状況（ステージ1開発中）

```bash
# UDPプロトコルのテストを実行
composer test tests/Unit/Common/Protocol/UdpProtocolTest.php

# パフォーマンステスト実行  
composer test tests/Integration/UdpProtocolPerformanceTest.php
```

### 将来の使用方法（実装予定）

```bash
# UDPサーバ起動
php src/Server/UdpServer.php --port=8080

# CLIクライアント起動
php src/Client/CliClient.php --server=localhost:8080

# チャットルーム作成（ステージ2）
php src/Client/RoomClient.php --create --room="MyRoom" --username="Alice"
```

## 📡 プロトコル仕様

### UDP メッセージフォーマット（ステージ1）

```
[usernamelen: 1byte][username: variable][message: variable]
最大サイズ: 4096 bytes
エンコーディング: UTF-8
```

### TCRP プロトコル（ステージ2予定）

```
ヘッダー (32 bytes):
[RoomNameSize: 1byte][Operation: 1byte][State: 1byte][OperationPayloadSize: 29bytes]

操作コード:
- 1: ルーム作成
- 2: ルーム参加

状態コード:
- 0: リクエスト
- 1: 応答
- 2: 完了
```

## 🧪 テスト

```bash
# 全テスト実行
composer test

# 単体テストのみ
composer test:unit

# 統合テストのみ  
composer test:integration

# カバレッジ付きテスト（実装予定）
composer test:coverage
```

### テスト統計（現在）
- **テスト総数**: 30件
- **アサーション数**: 10,064件
- **パフォーマンステスト**: 10,000+ operations/second 検証済み

## 🔧 開発コマンド

```bash
# コード品質
composer cs:check          # コードスタイルチェック
composer cs:fix            # コードスタイル自動修正
composer analyse           # 静的解析（PHPStan Level 8）

# Git Hooks
git config core.hooksPath .husky  # Git hooks有効化
```

## 📊 パフォーマンス要件

| 項目 | 要件 | 現在の実装 |
|------|------|------------|
| パケット処理速度 | 10,000+ packets/sec | ✅ 達成 |
| 同時接続数 | 1,000+ connections | 🚧 実装中 |
| チャットルーム数 | 500+ rooms | 📅 ステージ2 |
| 応答時間 | <10ms (ローカル) | ✅ 達成 |
| メモリ使用量 | <100MB (1000接続) | 🚧 実装中 |

## 🏗️ アーキテクチャ

```
src/
├── Server/
│   ├── UdpServer.php      # リアルタイムメッセージ処理
│   └── TcpServer.php      # ルーム管理（ステージ2）
├── Client/
│   ├── CliClient.php      # CLIチャットクライアント
│   ├── RoomClient.php     # ルーム対応クライアント
│   └── GuiClient/         # Electron.js GUI（ステージ3）
├── Common/
│   ├── Protocol/          # メッセージフォーマット処理
│   ├── Utils/             # 共通ユーティリティ
│   └── Models/            # データ構造
tests/
├── Unit/                  # 単体テスト
└── Integration/           # 統合テスト
```

## 🤝 開発ワークフロー

1. **Issue作成**: 適切なテンプレートでissueを作成
2. **ブランチ作成**: `task/stage1-feature-name` 形式
3. **実装**: TDD・品質チェック準拠
4. **テスト**: 全テスト通過確認
5. **PR作成**: レビュー用Pull Request
6. **マージ**: レビュー完了後main統合

### ブランチ命名規則

- `task/stage1-*`: ステージ1タスク
- `task/stage2-*`: ステージ2タスク
- `task/stage3-*`: ステージ3タスク
- `fix/*`: バグ修正

## 📈 進捗状況

### 完了済み ✅
- [x] プロジェクト基盤構築
- [x] UDPメッセージプロトコル実装
- [x] 包括的テストスイート構築
- [x] 品質管理体制確立

### 実装中 🚧
- [ ] UDPサーバ実装
- [ ] CLIクライアント実装

### 予定 📅
- [ ] チャットルーム機能（ステージ2）
- [ ] 暗号化・GUI機能（ステージ3）

## 🔒 セキュリティ

- UTF-8エンコーディング検証
- パケットサイズ制限（4096バイト）
- 入力値サニタイゼーション
- RSA暗号化（ステージ3予定）

## 📚 ドキュメント

- [プロジェクト仕様書](CLAUDE.md)
- [GitHub管理システム](.github/)
- [マイルストーン定義](.github/MILESTONES.md)

## 🤖 自動化

- **Git Hooks**: コミット前品質チェック（Husky）
- **CI/CD**: 予定（GitHub Actions）
- **コード品質**: PSR-12, PHPStan Level 8

## 📞 サポート

プロジェクトに関する質問やissueは、[GitHub Issues](https://github.com/takeshi-arihori/online_chatmessenger/issues)で報告してください。

---

🤖 Generated with [Claude Code](https://claude.ai/code)
