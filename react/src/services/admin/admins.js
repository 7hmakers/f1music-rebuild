import { request } from "utils/admin";
import { api } from "utils/admin/config";
import useSWR from "swr";
import useSWRMutation from "swr/mutation";

const { admins } = api;

export const useAdmins = () => {
  const swr = useSWR(admins, () =>
    request({
      url: admins,
      method: "get",
    }).then((data) => data.admins)
  );

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

  return { ...swr, add, remove };
};
