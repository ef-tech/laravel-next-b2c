import { render as rtlRender, RenderOptions } from '@testing-library/react';
import { ReactElement } from 'react';

interface CustomRenderOptions extends RenderOptions {
  // 将来的にProvider追加用の拡張ポイント
}

export function render(
  ui: ReactElement,
  options?: CustomRenderOptions,
): ReturnType<typeof rtlRender> {
  return rtlRender(ui, options);
}

// @testing-library/reactの全エクスポートを再エクスポート
export * from '@testing-library/react';
