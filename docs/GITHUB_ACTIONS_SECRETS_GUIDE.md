# GitHub Actions Secrets 設定ガイド

## 目次

1. [GitHub Actions Secretsとは](#github-actions-secretsとは)
2. [Secrets命名規約](#secrets命名規約)
3. [Repository Secrets vs Environment Secrets](#repository-secrets-vs-environment-secrets)
4. [必須Secrets一覧](#必須secrets一覧)
5. [Secrets設定手順](#secrets設定手順)
6. [CI/CDワークフローでのSecrets参照](#cicdワークフローでのsecrets参照)
7. [セキュリティベストプラクティス](#セキュリティベストプラクティス)
8. [トラブルシューティング](#トラブルシューティング)

---

## GitHub Actions Secretsとは

GitHub Actions Secretsは、CI/CD環境で機密情報（API キー、パスワード、トークン等）を安全に管理するための機能です。

### 主な特徴

- **暗号化**: Secretsは暗号化されて保存され、ワークフロー実行時のみ復号化されます
- **マスキング**: ログ出力時に自動的にマスキングされ、機密情報が漏洩しません
- **アクセス制御**: リポジトリやEnvironmentごとにアクセス権限を制御できます
- **監査ログ**: Secretsの作成・更新・削除はすべて監査ログに記録されます

### なぜSecretsが重要か

1. **セキュリティ**: 機密情報をコードやログに含めることを防ぎます
2. **環境分離**: 開発・ステージング・本番環境ごとに異なる認証情報を使用できます
3. **コンプライアンス**: セキュリティ監査やコンプライアンス要件を満たします
4. **チーム協力**: 機密情報を共有せずに、チームメンバー全員がCI/CDを利用できます

---

## Secrets命名規約

このプロジェクトでは以下の命名規約を採用しています。

### 基本パターン

```
{サービス}_{環境}_{変数名}
```

### 例

- `LARAVEL_PROD_DB_PASSWORD` - 本番環境のLaravel APIデータベースパスワード
- `LARAVEL_STAGING_APP_KEY` - ステージング環境のLaravel APIアプリケーションキー
- `NEXTJS_PROD_API_URL` - 本番環境のNext.js APIエンドポイント
- `E2E_TEST_USER_EMAIL` - E2Eテスト用ユーザーメールアドレス

### サービスプレフィックス

- `LARAVEL_` - Laravel API関連
- `NEXTJS_` - Next.jsアプリケーション関連
- `E2E_` - E2Eテスト関連
- `DOCKER_` - Docker関連
- `GITHUB_` - GitHub関連（トークン、PAT等）

### 環境サフィックス

- `_PROD` - 本番環境
- `_STAGING` - ステージング環境
- `_DEV` - 開発環境
- `_TEST` - テスト環境

### 注意事項

- **大文字・アンダースコア**: すべて大文字、単語区切りはアンダースコア
- **明確な名前**: 変数名から用途が明確にわかるように命名
- **環境の明示**: 環境ごとに異なる値を持つ場合は環境サフィックスを必ず含める

---

## Repository Secrets vs Environment Secrets

GitHub Actionsでは、Secretsを**Repository Secrets**と**Environment Secrets**の2種類で管理できます。

### Repository Secrets

**用途**: すべての環境で共通の機密情報、または環境分離が不要な情報

**特徴**:
- リポジトリ全体で利用可能
- すべてのブランチ・ワークフローから参照可能
- アクセス制御は最小限（リポジトリの管理者のみ設定可能）

**使用例**:
- CI/CD用のサービスアカウントトークン
- テスト用の固定認証情報
- 開発環境のデータベース接続情報

**設定手順**:
1. GitHub リポジトリページへ移動
2. **Settings** > **Secrets and variables** > **Actions**
3. **New repository secret** をクリック
4. Secret名と値を入力して保存

### Environment Secrets

**用途**: 環境ごとに異なる機密情報、本番環境のデプロイ承認が必要な場合

**特徴**:
- Environment（production、staging等）ごとに個別管理
- デプロイ承認フローを設定可能
- アクセス制御が強力（特定のブランチからのみアクセス可能等）

**使用例**:
- 本番環境のデータベースパスワード
- 本番環境のAPI キー
- ステージング環境のサードパーティサービストークン

**設定手順**:
1. GitHub リポジトリページへ移動
2. **Settings** > **Environments**
3. Environment（例: `production`）を作成または選択
4. **Add secret** をクリック
5. Secret名と値を入力して保存

### 使い分け基準

| 条件 | Repository Secrets | Environment Secrets |
|------|-------------------|---------------------|
| 環境ごとに値が異なる | ❌ | ✅ |
| デプロイ承認が必要 | ❌ | ✅ |
| 本番環境への影響が大きい | ❌ | ✅ |
| テスト・CI専用 | ✅ | ❌ |
| すべてのブランチで利用 | ✅ | ❌ |

**推奨**: 本番環境の機密情報は**必ずEnvironment Secrets**を使用してください。

---

## 必須Secrets一覧

### Laravel API（Backend）

#### 本番環境（Environment: `production`）

| Secret名 | 説明 | 例 |
|---------|------|-----|
| `LARAVEL_PROD_APP_KEY` | Laravel アプリケーションキー | `base64:ランダム文字列` |
| `LARAVEL_PROD_DB_PASSWORD` | データベースパスワード | `強力なパスワード` |
| `LARAVEL_PROD_DB_USERNAME` | データベースユーザー名 | `laravel_user` |
| `LARAVEL_PROD_DB_HOST` | データベースホスト | `db.example.com` |
| `LARAVEL_PROD_REDIS_PASSWORD` | Redisパスワード | `強力なパスワード` |

#### ステージング環境（Environment: `staging`）

| Secret名 | 説明 |
|---------|------|
| `LARAVEL_STAGING_APP_KEY` | Laravel アプリケーションキー |
| `LARAVEL_STAGING_DB_PASSWORD` | データベースパスワード |

#### CI/CD専用（Repository Secrets）

| Secret名 | 説明 | 備考 |
|---------|------|------|
| `LARAVEL_TEST_DB_PASSWORD` | テスト用DBパスワード | CI環境で使用 |

### Next.js Frontend

#### 本番環境（Environment: `production`）

| Secret名 | 説明 | 例 |
|---------|------|-----|
| `NEXTJS_PROD_API_URL` | 本番APIエンドポイント | `https://api.example.com` |

#### ステージング環境（Environment: `staging`）

| Secret名 | 説明 |
|---------|------|
| `NEXTJS_STAGING_API_URL` | ステージングAPIエンドポイント |

### E2Eテスト（Repository Secrets）

| Secret名 | 説明 | 例 |
|---------|------|-----|
| `E2E_TEST_USER_EMAIL` | E2Eテスト用ユーザーメール | `test@example.com` |
| `E2E_TEST_USER_PASSWORD` | E2Eテスト用ユーザーパスワード | `TestPassword123` |
| `E2E_ADMIN_USER_EMAIL` | E2Eテスト用管理者メール | `admin@example.com` |
| `E2E_ADMIN_USER_PASSWORD` | E2Eテスト用管理者パスワード | `AdminPassword123` |

### Docker・その他（Repository Secrets）

| Secret名 | 説明 |
|---------|------|
| `DOCKERHUB_USERNAME` | Docker Hub ユーザー名 |
| `DOCKERHUB_TOKEN` | Docker Hub アクセストークン |

---

## Secrets設定手順

### 1. Repository Secretsの設定

1. GitHubリポジトリページへ移動
2. **Settings** タブをクリック
3. 左サイドバーから **Secrets and variables** > **Actions** を選択
4. **Repository secrets** セクションで **New repository secret** をクリック
5. **Name** に Secret名を入力（例: `E2E_TEST_USER_EMAIL`）
6. **Secret** に値を入力
7. **Add secret** をクリック

### 2. Environment Secretsの設定

#### Environmentの作成

1. GitHubリポジトリページへ移動
2. **Settings** タブをクリック
3. 左サイドバーから **Environments** を選択
4. **New environment** をクリック
5. Environment名を入力（例: `production`）
6. **Configure environment** をクリック

#### デプロイ承認の設定（本番環境推奨）

1. **Required reviewers** にチェック
2. 承認者を追加（最低2名推奨）
3. **Save protection rules** をクリック

#### Environment Secretsの追加

1. Environment設定ページで **Add secret** をクリック
2. **Name** に Secret名を入力（例: `LARAVEL_PROD_DB_PASSWORD`）
3. **Value** に値を入力
4. **Add secret** をクリック

### 3. 設定の確認

設定後、以下を確認してください：

- [ ] Secret名が命名規約に従っている
- [ ] 本番環境のSecretsはEnvironment Secretsに設定されている
- [ ] デプロイ承認者が設定されている（本番環境）
- [ ] 必須Secrets一覧のすべてが設定されている

---

## CI/CDワークフローでのSecrets参照

### 基本的な参照方法

```yaml
steps:
  - name: Deploy to production
    run: |
      echo "Deploying with DB password"
    env:
      DB_PASSWORD: ${{ secrets.LARAVEL_PROD_DB_PASSWORD }}
```

### Repository Secretsの参照

```yaml
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - name: Run tests
        run: npm test
        env:
          E2E_USER_EMAIL: ${{ secrets.E2E_TEST_USER_EMAIL }}
          E2E_USER_PASSWORD: ${{ secrets.E2E_TEST_USER_PASSWORD }}
```

### Environment Secretsの参照

```yaml
jobs:
  deploy:
    runs-on: ubuntu-latest
    environment: production  # Environment指定が必須
    steps:
      - name: Deploy to production
        run: ./deploy.sh
        env:
          DB_PASSWORD: ${{ secrets.LARAVEL_PROD_DB_PASSWORD }}
          API_KEY: ${{ secrets.LARAVEL_PROD_API_KEY }}
```

### 複数のSecretsをまとめて設定

```yaml
jobs:
  build:
    runs-on: ubuntu-latest
    env:
      # 共通環境変数
      NODE_ENV: production
      API_URL: ${{ secrets.NEXTJS_PROD_API_URL }}
    steps:
      - name: Build application
        run: npm run build
        env:
          # ステップ固有の環境変数
          BUILD_TOKEN: ${{ secrets.BUILD_TOKEN }}
```

### 注意事項

- **Secret参照構文**: `${{ secrets.SECRET_NAME }}`
- **Environment指定**: Environment Secretsを使用する場合、`environment: 環境名` を必ず指定
- **ログマスキング**: Secretsは自動的にログでマスキングされますが、エコーや出力は避けてください

---

## セキュリティベストプラクティス

### 1. 定期的なローテーション

- **本番環境のSecrets**: 3ヶ月ごとにローテーション
- **テスト環境のSecrets**: 6ヶ月ごとにローテーション
- **侵害の疑い**: 即座にローテーション

### 2. アクセス制御

- **最小権限の原則**: 必要最小限のアクセス権のみ付与
- **Environment Secrets**: 本番環境には必ずEnvironment Secretsを使用
- **デプロイ承認**: 本番環境のデプロイには必ず承認者を設定（最低2名）

### 3. 監査とモニタリング

- **監査ログ確認**: 月1回、Secretsの作成・更新・削除ログを確認
- **不審なアクセス検知**: GitHub Actionsのワークフロー実行ログを定期確認
- **インシデント対応**: Secrets漏洩の疑いがある場合は即座に無効化

### 4. Secrets管理のDo/Don't

#### ✅ Do（推奨）

- Environment Secretsを本番環境に使用する
- 命名規約に従ってSecretsを命名する
- デプロイ承認フローを設定する
- 定期的にSecretsをローテーションする
- 監査ログを定期的に確認する

#### ❌ Don't（非推奨）

- Secretsをログに出力しない
- Secretsをコードやコミットに含めない
- Secretsを環境変数として永続化しない
- すべてのSecretsをRepository Secretsに設定しない
- Secretsを複数の環境で共有しない

### 5. 緊急時の対応手順

Secrets漏洩の疑いがある場合：

1. **即座に無効化**: 該当するSecretを削除または変更
2. **影響範囲調査**: どのリソースがアクセスされた可能性があるか確認
3. **関連サービスの認証情報変更**: データベース、API キー等を変更
4. **監査ログ確認**: GitHub ActionsとAWS/GCPのログを確認
5. **インシデント報告**: チームリーダーとセキュリティチームに報告
6. **再発防止策**: 原因を分析し、再発防止策を実施

---

## トラブルシューティング

### 1. Secret not found エラー

**エラーメッセージ**:
```
Error: Secret LARAVEL_PROD_DB_PASSWORD is not set
```

**原因**:
- Secretが設定されていない
- Secret名が間違っている
- Environment指定が間違っている

**解決方法**:
1. GitHub Settings > Secrets and variables > Actions を確認
2. Secret名のスペルミスを確認
3. ワークフローで `environment:` が正しく指定されているか確認
4. Environment Secretsの場合、Environmentが正しく作成されているか確認

### 2. Secret値が空

**症状**:
- ワークフローは成功するが、Secret値が反映されていない
- ログに空文字列が表示される

**原因**:
- Secret値が設定されていない
- Secret値に余分なスペースが含まれている

**解決方法**:
1. GitHub Settings でSecret値を再確認
2. Secret値を再設定（コピー&ペーストの余分なスペースに注意）

### 3. Environment Secretsにアクセスできない

**エラーメッセージ**:
```
Error: Resource not accessible by integration
```

**原因**:
- ワークフローで `environment:` が指定されていない
- Environment名が間違っている
- ブランチ保護ルールで該当ブランチがブロックされている

**解決方法**:
1. ワークフローに `environment: production` を追加
2. Environment名を確認（Settings > Environments）
3. Environment の **Deployment branches** 設定を確認

### 4. デプロイ承認が動作しない

**症状**:
- 承認者を設定したが、承認なしでデプロイされる

**原因**:
- Environment の **Required reviewers** が設定されていない
- ワークフローで `environment:` が指定されていない

**解決方法**:
1. Environment設定で **Required reviewers** を確認
2. 承認者を追加（最低2名推奨）
3. ワークフローで `environment: production` を指定

### 5. よくある質問（FAQ）

**Q: Secretsは何文字まで設定できますか？**
A: 最大64KB（約65,000文字）まで設定可能です。

**Q: Secretsは暗号化されていますか？**
A: はい、保存時は暗号化され、ワークフロー実行時のみ復号化されます。

**Q: Secretsを複数のリポジトリで共有できますか？**
A: GitHub Organization Secretsを使用することで、複数のリポジトリでSecretsを共有できます。

**Q: Secretsの変更履歴は確認できますか？**
A: 監査ログで変更履歴を確認できますが、値そのものは確認できません。

**Q: Secretsを削除するとワークフローはどうなりますか？**
A: 該当Secretを参照しているワークフローは失敗します。削除前に影響範囲を確認してください。

---

## 関連ドキュメント

- [環境変数セキュリティガイド](./ENVIRONMENT_VARIABLE_SECURITY_GUIDE.md)
- [README - 環境変数管理セクション](../README.md#環境変数管理)
- [GitHub公式ドキュメント - Encrypted secrets](https://docs.github.com/en/actions/security-guides/encrypted-secrets)

---

## 更新履歴

- 2025-01-15: 初版作成
