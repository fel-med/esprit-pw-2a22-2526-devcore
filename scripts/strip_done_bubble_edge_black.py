"""Flood edge-connected near-black to transparent; keeps white bubble + ink (not connected to outer black)."""
from __future__ import annotations

import collections
import sys
from pathlib import Path

from PIL import Image


def main() -> int:
    if len(sys.argv) != 3:
        print("usage: strip_done_bubble_edge_black.py <input.png> <output.png>", file=sys.stderr)
        return 1
    inp = Path(sys.argv[1])
    outp = Path(sys.argv[2])
    im = Image.open(inp).convert("RGBA")
    w, h = im.size
    px = im.load()
    assert px is not None

    def is_outer_black(r: int, g: int, b: int, a: int) -> bool:
        if a < 80:
            return False
        return max(r, g, b) <= 58

    visited = bytearray(w * h)

    def idx(x: int, y: int) -> int:
        return y * w + x

    q: collections.deque[tuple[int, int]] = collections.deque()
    for x in range(w):
        for y in (0, h - 1):
            r, g, b, a = px[x, y]
            if is_outer_black(r, g, b, a):
                ii = idx(x, y)
                if not visited[ii]:
                    visited[ii] = 1
                    q.append((x, y))
    for y in range(h):
        for x in (0, w - 1):
            r, g, b, a = px[x, y]
            if is_outer_black(r, g, b, a):
                ii = idx(x, y)
                if not visited[ii]:
                    visited[ii] = 1
                    q.append((x, y))

    cleared = 0
    while q:
        x, y = q.popleft()
        px[x, y] = (0, 0, 0, 0)
        cleared += 1
        for dx, dy in ((1, 0), (-1, 0), (0, 1), (0, -1)):
            nx, ny = x + dx, y + dy
            if nx < 0 or ny < 0 or nx >= w or ny >= h:
                continue
            ii = idx(nx, ny)
            if visited[ii]:
                continue
            r, g, b, a = px[nx, ny]
            if not is_outer_black(r, g, b, a):
                continue
            visited[ii] = 1
            q.append((nx, ny))

    if cleared < 32:
        print(f"warning: only {cleared} pixels cleared; output may still look wrong", file=sys.stderr)
    im.save(outp, "PNG", compress_level=9)
    print(f"cleared={cleared} -> {outp}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
