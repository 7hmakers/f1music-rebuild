import { atom, useAtom } from "jotai";
import { useEffect } from "react";
import { useSWRConfig } from "swr";
import { api } from "utils/config";

const { status } = api;

const getCookie = (name) => {
  const match = document.cookie.match(new RegExp(`(^| )${name}=([^;]+)`));
  if (match) {
    return match[2];
  }
  return null;
};

const decodeCookie = (value) => {
  if (!value) {
    return {};
  }
  try {
    const json = atob(decodeURIComponent(value));
    return JSON.parse(json);
  } catch (e) {
    console.log(e);
    return {};
  }
};

export const getUser = () => {
  const user = getCookie("f1music_user");
  const auth = getCookie("f1music_auth");
  if (user || auth) {
    return decodeCookie(user);
  }
  return null;
};

const userAtom = atom(null);

const useUser = () => {
  const [user, setUser] = useAtom(userAtom);
  const swr = useSWRConfig();

  // Clear cache when user changes, e.g. vote list
  const clearCache = () =>
    swr.mutate((key) => key !== status, undefined, { revalidate: false });

  const mutate = () => {
    setUser(getUser());
    clearCache();
  };

  // Only sync the cookie on mount. Do NOT clear the cache here: a fast-resolving
  // SWR request on the same page (e.g. the admin list on localhost) could be
  // wiped right after it loads, leaving the list stuck empty/loading.
  useEffect(() => setUser(getUser()), [setUser]);

  return { user, mutate };
};

export default useUser;
