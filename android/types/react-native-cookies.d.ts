declare module 'react-native-cookies' {
  export interface Cookie {
    name: string;
    value: string;
    domain?: string;
    path?: string;
    expires?: string;
    secure?: boolean;
    httpOnly?: boolean;
  }

  export interface CookieManager {
    set(url: string, cookie: Cookie): Promise<boolean>;
    get(url: string): Promise<{ [key: string]: Cookie }>;
    clearAll(): Promise<boolean>;
    clearByName(name: string): Promise<boolean>;
  }

  const CookieManager: CookieManager;
  export default CookieManager;
}
