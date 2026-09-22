import 'package:flutter/material.dart';
import '../constants/colors.dart';

class GarmentMeasureField extends StatefulWidget {
  final String label;
  final double initialValue;
  final ValueChanged<double> onChanged;
  final double step;
  final double min;
  final double max;

  const GarmentMeasureField({
    super.key,
    required this.label,
    required this.initialValue,
    required this.onChanged,
    this.step = 0.5,
    this.min = 5.0,
    this.max = 70.0,
  });

  @override
  State<GarmentMeasureField> createState() => _GarmentMeasureFieldState();
}

class _GarmentMeasureFieldState extends State<GarmentMeasureField> {
  late TextEditingController _controller;
  late double _value;

  @override
  void initState() {
    super.initState();
    _value = widget.initialValue;
    _controller = TextEditingController(text: _formatValue(_value));
  }

  @override
  void didUpdateWidget(covariant GarmentMeasureField oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.initialValue != widget.initialValue) {
      _value = widget.initialValue;
      _controller.text = _formatValue(_value);
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  String _formatValue(double v) {
    if (v == v.roundToDouble()) {
      return v.toInt().toString();
    }
    return v.toStringAsFixed(1);
  }

  void _updateVal(double newVal) {
    if (newVal < widget.min) newVal = widget.min;
    if (newVal > widget.max) newVal = widget.max;
    setState(() {
      _value = newVal;
      _controller.text = _formatValue(_value);
    });
    widget.onChanged(_value);
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: AppColors.cardBg,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.cardBorder),
      ),
      child: Row(
        children: [
          // Label
          Expanded(
            child: Text(
              widget.label,
              style: const TextStyle(
                color: AppColors.textPrimary,
                fontWeight: FontWeight.w600,
                fontSize: 14,
              ),
            ),
          ),

          // Decrement Button (-)
          InkWell(
            onTap: () => _updateVal(_value - widget.step),
            borderRadius: BorderRadius.circular(8),
            child: Container(
              width: 36,
              height: 36,
              decoration: BoxDecoration(
                color: AppColors.surfaceLight,
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: AppColors.cardBorder),
              ),
              child: const Icon(Icons.remove, color: AppColors.goldLight, size: 18),
            ),
          ),
          const SizedBox(width: 8),

          // Numeric TextField with Inches unit
          Container(
            width: 72,
            height: 38,
            decoration: BoxDecoration(
              color: AppColors.inputBg,
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: AppColors.gold.withValues(alpha: 0.5)),
            ),
            alignment: Alignment.center,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                IntrinsicWidth(
                  child: TextField(
                    controller: _controller,
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      color: AppColors.gold,
                      fontWeight: FontWeight.bold,
                      fontSize: 16,
                    ),
                    decoration: const InputDecoration(
                      border: InputBorder.none,
                      contentPadding: EdgeInsets.zero,
                      isDense: true,
                    ),
                    onChanged: (text) {
                      final parsed = double.tryParse(text);
                      if (parsed != null) {
                        _value = parsed;
                        widget.onChanged(_value);
                      }
                    },
                  ),
                ),
                const Text(
                  '"',
                  style: TextStyle(
                    color: AppColors.textMuted,
                    fontWeight: FontWeight.bold,
                    fontSize: 14,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),

          // Increment Button (+)
          InkWell(
            onTap: () => _updateVal(_value + widget.step),
            borderRadius: BorderRadius.circular(8),
            child: Container(
              width: 36,
              height: 36,
              decoration: BoxDecoration(
                color: AppColors.surfaceLight,
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: AppColors.cardBorder),
              ),
              child: const Icon(Icons.add, color: AppColors.goldLight, size: 18),
            ),
          ),
        ],
      ),
    );
  }
}
