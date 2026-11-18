import { describe, expect, test } from '@jest/globals';
import stickyAttributes from '../../src/forum/utils/stickyAttributes';

const state = { isSticky: false, isStickiest: false, isTagSticky: true, selectedTagIds: [1, 2] };

describe('sticky modal request permissions', () => {
  test('a tag delegate never submits forbidden super or normal sticky fields', () => {
    expect(stickyAttributes({ canSticky: () => false, canStickiest: () => false, canTagSticky: () => true }, state)).toEqual({
      isTagSticky: true,
      stickyTagIds: [1, 2],
    });
  });

  test('a super sticky delegate never submits tag sticky fields', () => {
    expect(stickyAttributes({ canStickiest: () => true, canTagSticky: () => false }, { ...state, isStickiest: true })).toEqual({
      isStickiest: true,
    });
  });

  test('a normal sticky delegate only submits the core sticky field', () => {
    expect(
      stickyAttributes({ canSticky: () => true, canStickiest: () => false, canTagSticky: () => false }, { ...state, isTagSticky: false, isSticky: true })
    ).toEqual({ isSticky: true });
  });

  test('disabling tag sticky clears selected IDs', () => {
    expect(stickyAttributes({ canStickiest: () => false, canTagSticky: () => true }, { ...state, isTagSticky: false })).toEqual({
      isTagSticky: false,
      stickyTagIds: [],
    });
  });

  test('the optional core sticky field is absent when core Sticky is unavailable', () => {
    expect(stickyAttributes({ canStickiest: () => false, canTagSticky: () => false }, state)).toEqual({});
  });
});
