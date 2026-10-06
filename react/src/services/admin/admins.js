import { request } from "utils/admin";
import { api } from "utils/admin/config";
import useSWR from "swr";
import useSWRMutation from "swr/mutation";

const { admins, adminSearch } = api;

export const useUsers = (page = 1, size = 10) => {
  const swr = useSWR([admins, page, size], () =>
    request({
      url: admins,
      method: "get",
      params: { page, size },
    }).then((data) => data.users)
  );

  const { list = [], total = 0 } = swr.data ?? {};

  const add = useSWRMutation(admins, async (_, { arg }) =>
    request({
      url: admins,
      method: "post",
      data: arg,
    }).then(() => swr.mutate())
  );

  const remove = useSWRMutation(admins, async (_, { arg: id }) =>
    request({
      url: admins,
      method: "delete",
      data: { id },
    }).then(() => swr.mutate())
  );

  const search = (id) =>
    request({
      url: adminSearch,
      method: "get",
      params: { id },
    });

  return { ...swr, list, total, add, remove, search };
};
