# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a real-time chat messenger system built with PHP 8.3, implementing a client-server architecture using UDP/TCP protocols. The project is structured in three stages:

- **Stage 1**: Basic UDP chat functionality
- **Stage 2**: Chat room features with TCP/UDP dual protocol
- **Stage 3**: Advanced features including RSA encryption and GUI client

## GitHub Issue Management

このプロジェクトは構造化されたGitHub issue管理システムを使用しています。

### Issue テンプレート
- **ステージ別タスク**: ステージ1〜3の実装タスク用の専用テンプレート
- **バグレポート**: プロジェクト特化のバグ報告テンプレート
- **機能リクエスト**: 新機能提案用の詳細テンプレート
- **汎用タスク**: その他の実装タスク用テンプレート

### ラベル体系
- **ステージ**: `stage-1`, `stage-2`, `stage-3`
- **コンポーネント**: `server`, `client`, `protocol`, `security`, `testing`, `gui`
- **優先度**: `priority-high`, `priority-medium`, `priority-low`
- **種類**: `task`, `bug`, `enhancement`, `documentation`

### マイルストーン
- **Milestone 1**: ステージ1完了（基本UDPチャット）
- **Milestone 2**: ステージ2完了（チャットルーム機能）
- **Milestone 3**: ステージ3完了（高度な機能）

## Development Commands

Since this is an early-stage project with no existing code, these commands will need to be implemented during initial setup:

```bash
# Install dependencies (once composer.json is created)
composer install

# Run tests (once test suite is implemented)
composer test
composer test:performance
composer test:integration

# Start servers (once implemented)
php src/Server/UdpServer.php --port=8080
php src/Server/TcpServer.php --port=8081

# Start clients (once implemented)
php src/Client/CliClient.php --server=localhost:8080
php src/Client/RoomClient.php --create --room="MyRoom" --username="Alice"
```

## Architecture

### Dual Protocol System
- **TCP**: Chat room management (creation, joining, authentication)
- **UDP**: Real-time messaging for performance

### Directory Structure
```
.github/
├── ISSUE_TEMPLATE/
│   ├── task_implementation.md  # 汎用タスクテンプレート
│   ├── stage1_task.md         # ステージ1専用テンプレート
│   ├── stage2_task.md         # ステージ2専用テンプレート
│   ├── stage3_task.md         # ステージ3専用テンプレート
│   ├── bug_report.md          # バグレポートテンプレート
│   ├── feature_request.md     # 機能リクエストテンプレート
│   └── config.yml             # テンプレート設定
├── pull_request_template.md   # PRテンプレート
└── MILESTONES.md              # マイルストーン定義
src/
├── Server/
│   ├── UdpServer.php      # Real-time message handling
│   └── TcpServer.php      # Room management
├── Client/
│   ├── CliClient.php      # CLI chat client
│   ├── RoomClient.php     # Room-aware client
│   └── GuiClient/         # Electron.js GUI (Stage 3)
├── Common/
│   ├── Protocol/          # Message format handlers
│   ├── Utils/             # Shared utilities
│   └── Models/            # Data structures
tests/
├── Unit/
└── Integration/
```

### Core Protocols

#### UDP Message Format (Stage 1)
```
[usernamelen: 1byte][username: variable][message: variable]
Max size: 4096 bytes
Encoding: UTF-8
```

#### TCRP Protocol (Stage 2)
```
Header (32 bytes):
[RoomNameSize: 1byte][Operation: 1byte][State: 1byte][OperationPayloadSize: 29bytes]

Operations:
- 1: Create room
- 2: Join room

States:
- 0: Request
- 1: Response  
- 2: Complete
```

## Performance Requirements

- 10,000+ packets/second processing
- 1,000+ concurrent client connections
- 500+ simultaneous chat rooms
- <10ms latency (local environment)
- <100MB memory usage (1000 connections)

## Technology Stack

- **Language**: PHP 8.3
- **Networking**: Socket API (UDP/TCP)
- **Async Processing**: ReactPHP / Swoole / pcntl_fork
- **Encryption**: OpenSSL (RSA, 2048-bit keys)
- **Testing**: PHPUnit
- **GUI**: Electron.js + Node.js (Stage 3)
- **Dependencies**: Composer

## Implementation Stages

### Stage 1: Basic UDP Chat
- CLI-based real-time chat
- UDP protocol for high-speed messaging
- Multiple concurrent client connections
- Automatic client management

### Stage 2: Chat Room Features
- Private chat room creation
- Token-based authentication
- TCP/UDP dual protocol system
- Host permission management

### Stage 3: Advanced Features
- Password-protected rooms
- RSA encrypted communication
- Electron.js GUI client
- Large-scale scaling support

## Security Considerations

- RSA public key cryptography (Stage 3)
- 2048-bit key length
- Client-server key exchange
- All message encryption
- No sensitive information in commits

## Development Workflow

### Issue-driven Development
1. **Issue作成**: 適切なテンプレートを使用してissueを作成
2. **ラベル付け**: ステージ、コンポーネント、優先度ラベルを設定
3. **マイルストーン設定**: 対応するマイルストーンに割り当て
4. **実装**: ブランチを作成して機能を実装
5. **テスト**: 必要なテスト項目を完了
6. **レビュー**: Pull Requestでコードレビューを実施
7. **マージ**: レビュー完了後にマージしてissueをクローズ

### ブランチ戦略とワークフロー

#### ブランチ命名規則
- `main`: プロダクション対応のメインブランチ
- `task/stage1-*`: ステージ1関連のタスクブランチ（例: `task/stage1-udp-protocol`）
- `task/stage2-*`: ステージ2関連のタスクブランチ（例: `task/stage2-tcp-server`）
- `task/stage3-*`: ステージ3関連のタスクブランチ（例: `task/stage3-rsa-encryption`）
- `fix/*`: バグ修正用ブランチ（例: `fix/server-memory-leak`）

#### タスク実装ワークフロー
1. **ブランチ作成**: 各タスク開始前に専用ブランチを作成
   ```bash
   git checkout -b task/stage1-project-setup
   ```

2. **実装**: タスクに必要な機能を実装
   - インターフェース定義
   - クラス実装
   - テスト作成
   - ドキュメント更新

3. **テスト実行**: 実装完了後に全テストを実行
   ```bash
   composer test
   composer cs:check
   composer analyse
   ```

4. **コミット・プッシュ**: タスク完了後にコミットしてプッシュ
   ```bash
   git add .
   git commit -m "feat: タスク#X の実装完了"
   git push origin task/stage1-xxx
   ```

5. **mainブランチへのマージ**: 必要に応じてmainにマージ
   ```bash
   git checkout main
   git merge task/stage1-xxx
   git push origin main
   ```

#### コミットメッセージ規則
- `feat:` - 新機能の追加
- `fix:` - バグ修正
- `refactor:` - リファクタリング
- `test:` - テストの追加・修正
- `docs:` - ドキュメントの更新
- `chore:` - その他のメンテナンス作業

### Git Hooks設定
プロジェクトには `.husky/` でpre-commitフックが設定されています：
- コード品質チェック
- テスト実行
- ドキュメント更新確認

## Development Notes

- All code should follow PHP 8.3 standards
- Use proper namespacing (autoloader required)
- UTF-8 encoding throughout
- Socket extension and OpenSSL extension required
- Comprehensive error handling for network operations
- Memory-efficient client connection management