import { memo } from "react";
import { Link } from "react-router-dom";
import { useHints } from "services/content";

import { BottomTips } from "components";

const Tips = ({ progress, time }) => {
  const { data: hints } = useHints();
  const [voted, total] = progress;

  if (voted < total) {
    const tip = (hints?.["hint.vote.progress"] ?? "")
      .replace("{voted}", voted)
      .replace("{left}", total - voted);

    return <BottomTips>{tip}</BottomTips>;
  }

  let rndTime;
  do {
    rndTime = Math.floor(Math.random() * 5 + 1);
  } while (rndTime === time);

  return (
    <BottomTips>
      您已投完本时段所有曲目，到
      <Link to={`/vote/${rndTime}`}>其他时段</Link>
      看看吧
    </BottomTips>
  );
};

export default memo(Tips);
