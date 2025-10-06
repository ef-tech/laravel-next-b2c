# Requirements Document

## GitHub Issue Information

**Issue**: [#65](https://github.com/ef-tech/laravel-next-b2c/issues/65) - Husky v10対応: pre-commit フックの非推奨警告修正
**Labels**: -
**Milestone**: -
**Assignees**: -

### Original Issue Description
## 背景

現在、gitコミット時に以下のHusky非推奨警告が表示されています：

```
husky - DEPRECATED

Please remove the following two lines from .husky/pre-commit:

#!/usr/bin/env sh
. "$(dirname -- "$0")/_/husky.sh"

They WILL FAIL in v10.0.0
```

## 問題

- Husky v9で非推奨になった古い形式を使用
- Husky v10では動作しなくなる予定
- 現在は警告のみで動作に問題なし

## 対応内容

`.husky/pre-commit` から以下の2行を削除：
```bash
#!/usr/bin/env sh
. "$(dirname -- "$0")/_/husky.sh"
```

## 参考

- Husky v9 Migration Guide: https://typicode.github.io/husky/migrating-from-v4-to-v9.html
- 影響範囲: `.husky/pre-commit` ファイルのみ
- 優先度: 低（現在動作中、v10リリース前に対応が必要）

## カテゴリ

**Maintenance** - Husky v10対応

## Extracted Information

### Technology Stack
**Backend**: -
**Frontend**: -
**Infrastructure**: -
**Tools**: Husky, Git

### Project Structure
```
.husky/pre-commit
```

### Development Services Configuration
なし

### Requirements Hints
Based on issue analysis:
- `.husky/pre-commit` ファイルから非推奨の2行を削除
- Husky v9で非推奨になった古い形式の削除
- Husky v10で動作しなくなる予定のコード修正

### TODO Items from Issue
- [ ] `.husky/pre-commit` から `#!/usr/bin/env sh` を削除
- [ ] `.husky/pre-commit` から `. "$(dirname -- "$0")/_/husky.sh"` を削除
- [ ] 削除後の動作確認

## Requirements

### Requirement 1: Husky v10互換性確保
**Objective:** 開発者として、Husky v10アップデート時にpre-commitフックが正常動作するように、非推奨警告を解消したい

#### Acceptance Criteria

1. WHEN `.husky/pre-commit` ファイルを確認した場合 THEN pre-commitフック SHALL `#!/usr/bin/env sh` の記述を含まない
2. WHEN `.husky/pre-commit` ファイルを確認した場合 THEN pre-commitフック SHALL `. "$(dirname -- "$0")/_/husky.sh"` の記述を含まない
3. WHEN 非推奨行削除後に `.husky/pre-commit` を確認した場合 THEN pre-commitフック SHALL `npx lint-staged` コマンドのみを含む

### Requirement 2: 既存機能の維持
**Objective:** 開発者として、pre-commitフック修正後も既存のlint機能が正常動作することを保証したい

#### Acceptance Criteria

1. WHEN ステージングエリアにファイルを追加してコミットを実行した場合 THEN pre-commitフック SHALL lint-stagedを自動実行する
2. WHEN lint-stagedが実行された場合 THEN pre-commitフック SHALL フォーマットチェックとlintチェックを正常実行する
3. IF lint-stagedでエラーが検出された場合 THEN pre-commitフック SHALL コミットを中断する

### Requirement 3: 後方互換性検証
**Objective:** 開発チームとして、Husky v9環境でも修正後のpre-commitフックが正常動作することを確認したい

#### Acceptance Criteria

1. WHEN Husky v9環境で修正後のpre-commitフックを実行した場合 THEN pre-commitフック SHALL エラーなく動作する
2. WHEN Husky v10環境で修正後のpre-commitフックを実行した場合 THEN pre-commitフック SHALL 非推奨警告を表示しない
3. WHEN Gitコミット実行時にpre-commitフックが起動した場合 THEN pre-commitフック SHALL 期待通りのlint処理を完了する
