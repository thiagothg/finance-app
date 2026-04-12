
export const APP_PREFIX = "/app";

export const PATHS = {
  app: APP_PREFIX,
  dashboard: `${APP_PREFIX}/dashboard`,
  transactions: `${APP_PREFIX}/transactions`,
  goals: `${APP_PREFIX}/goals`,
  management: {
    accounts: `${APP_PREFIX}/management/accounts`,
    categories: `${APP_PREFIX}/management/categories`,
  },
  settings: {
    root: `${APP_PREFIX}/settings`,
    profile: `${APP_PREFIX}/settings/profile`,
    account: `${APP_PREFIX}/settings/account`,
    appearance: `${APP_PREFIX}/settings/appearance`,
    notifications: `${APP_PREFIX}/settings/notifications`,
    display: `${APP_PREFIX}/settings/display`,
  },
  auth: {
    login: "/auth/login",
    validateCode: "/auth/validate-code",
    forgotPassword: "/auth/forgot-password",
  },
  public: {
    home: "/",
  }
};
