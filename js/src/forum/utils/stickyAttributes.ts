interface StickyPermissions {
  canSticky?: () => boolean;
  canStickiest: () => boolean;
  canTagSticky: () => boolean;
}

interface StickyState {
  isSticky: boolean;
  isStickiest: boolean;
  isTagSticky: boolean;
  selectedTagIds: number[];
}

export default function stickyAttributes(permissions: StickyPermissions, state: StickyState): Record<string, boolean | number[]> {
  const attributes: Record<string, boolean | number[]> = {};

  if (permissions.canStickiest()) {
    attributes.isStickiest = state.isStickiest;
  }

  if (permissions.canTagSticky()) {
    attributes.isTagSticky = state.isTagSticky;
    attributes.stickyTagIds = state.isTagSticky ? state.selectedTagIds : [];
  }

  if (permissions.canSticky?.()) {
    attributes.isSticky = state.isSticky && !state.isStickiest && !state.isTagSticky;
  }

  return attributes;
}
