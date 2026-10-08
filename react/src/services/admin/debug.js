import { request } from "utils/admin";
import { api } from "utils/admin/config";
import useSWR from "swr";

const { debug } = api;

export const useDebug = () =>
  useSWR(debug, () =>
    request({
      url: debug,
      method: "get",
    }).then((data) => data.debug)
  );
