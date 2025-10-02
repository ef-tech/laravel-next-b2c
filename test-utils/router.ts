import mockRouter from 'next-router-mock';

export function setupRouter(options: {
  pathname?: string;
  query?: Record<string, string>;
}): void {
  const { pathname = '/', query = {} } = options;

  const queryString = new URLSearchParams(query).toString();
  const url = queryString ? `${pathname}?${queryString}` : pathname;

  mockRouter.setCurrentUrl(url);
}
