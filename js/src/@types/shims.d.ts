import 'flarum/common/models/Discussion';

declare module 'flarum/common/models/Discussion' {
  export default interface Discussion {
    isStickiest(): boolean;
    isTagSticky(): boolean;
    canStickiest(): boolean;
    canTagSticky(): boolean;
    stickyTags(): Tag[];
    // flarum/sticky'den gelen opsiyonel metodlar
    isSticky?(): boolean;
    canSticky?(): boolean;
  }
}