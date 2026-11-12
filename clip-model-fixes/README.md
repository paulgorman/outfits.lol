# clip-model-fixes

Corrected files for CLIP models that don't load in TransformersPHP as
downloaded. `offline-classify.php` copies everything in here into
`.transformers-cache/` (same folder layout) before it loads a model.
TransformersPHP only downloads files that aren't in the cache yet, so a
wiped cache or a fresh download still ends up with these fixed versions.

## patrickjohncyh/fashion-clip (fixed 2026-11-11)

Fashion-clip is a retrained `openai/clip-vit-base-patch32`, saved by an
older Hugging Face that wrote its configs in an older format:

- `preprocessor_config.json`: says `"size": 224`, and TransformersPHP wants
  `{"shortest_edge": 224}` ("Cannot assign int to property ... $size").
  Replaced with Xenova/clip-vit-base-patch32's file: same 224 size, same
  color mean and std numbers, newer format.
- `tokenizer_config.json`: missing `model_max_length` (77), so the tokenizer
  crashed ("truncateHelper(): Argument #2 ($length) must be of type int,
  null given"). Replaced with Xenova/clip-vit-base-patch32's file. The
  vocabulary in `tokenizer.json` is identical (49,408 words, checked), so
  fashion's own `tokenizer.json` is left alone.

Fashion-clip only ships the full-size `onnx/model.onnx` (606MB, ~1.35GB RAM
when running), not `onnx/model_quantized.onnx`, which is why
`offline-classify.php` lists it in `$clip_models_that_only_come_full_size`.

If someday we make a quantized copy (INT8, ~154MB, ~450MB RAM, made with
Python's `onnxruntime.quantization` on some other machine, since there is no
Python on irev): it's too big for git, so DON'T put it in here. Keep it
somewhere backed up, and write down in this file where it lives and how it
was made. Then re-run the bake-off before trusting it.
