import 'package:flutter/material.dart';
import '../constants/colors.dart';

class SignaturePadWidget extends StatefulWidget {
  final ValueChanged<String> onSignatureChanged;

  const SignaturePadWidget({
    super.key,
    required this.onSignatureChanged,
  });

  @override
  State<SignaturePadWidget> createState() => _SignaturePadWidgetState();
}

class _SignaturePadWidgetState extends State<SignaturePadWidget> {
  final List<List<Offset>> _strokes = [];
  List<Offset>? _currentStroke;

  void _clear() {
    setState(() {
      _strokes.clear();
      _currentStroke = null;
    });
    widget.onSignatureChanged('');
  }

  void _undo() {
    if (_strokes.isNotEmpty) {
      setState(() {
        _strokes.removeLast();
      });
      _notifySvg();
    }
  }

  void _notifySvg() {
    if (_strokes.isEmpty) {
      widget.onSignatureChanged('');
      return;
    }

    final buffer = StringBuffer();
    buffer.write('<svg viewBox="0 0 320 180" xmlns="http://www.w3.org/2000/svg">');
    for (final stroke in _strokes) {
      if (stroke.length < 2) continue;
      buffer.write('<path d="M ${stroke[0].dx} ${stroke[0].dy}');
      for (int i = 1; i < stroke.length; i++) {
        buffer.write(' L ${stroke[i].dx} ${stroke[i].dy}');
      }
      buffer.write('" fill="none" stroke="#D4AF37" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>');
    }
    buffer.write('</svg>');
    widget.onSignatureChanged(buffer.toString());
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.gold.withValues(alpha: 0.6), width: 1.5),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.3),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          // Header with tools
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: const BoxDecoration(
              color: AppColors.surfaceLight,
              borderRadius: BorderRadius.vertical(top: Radius.circular(14)),
            ),
            child: Row(
              children: [
                const Icon(Icons.draw, color: AppColors.gold, size: 18),
                const SizedBox(width: 8),
                const Expanded(
                  child: Text(
                    'Digital Signature (हस्ताक्षर)',
                    style: TextStyle(
                      color: AppColors.textPrimary,
                      fontWeight: FontWeight.bold,
                      fontSize: 13,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                TextButton.icon(
                  onPressed: _undo,
                  icon: const Icon(Icons.undo, size: 14, color: AppColors.textSecondary),
                  label: const Text('Undo', style: TextStyle(color: AppColors.textSecondary, fontSize: 11)),
                  style: TextButton.styleFrom(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
                    minimumSize: Size.zero,
                    tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                  ),
                ),
                const SizedBox(width: 4),
                TextButton.icon(
                  onPressed: _clear,
                  icon: const Icon(Icons.delete_outline, size: 14, color: AppColors.rose),
                  label: const Text('Clear', style: TextStyle(color: AppColors.rose, fontSize: 11)),
                  style: TextButton.styleFrom(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
                    minimumSize: Size.zero,
                    tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                  ),
                ),
              ],
            ),
          ),

          // Canvas Area
          SizedBox(
            height: 180,
            width: double.infinity,
            child: ClipRRect(
              borderRadius: const BorderRadius.vertical(bottom: Radius.circular(14)),
              child: GestureDetector(
                onPanStart: (details) {
                  setState(() {
                    _currentStroke = [details.localPosition];
                    _strokes.add(_currentStroke!);
                  });
                },
                onPanUpdate: (details) {
                  setState(() {
                    _currentStroke?.add(details.localPosition);
                  });
                },
                onPanEnd: (_) {
                  _notifySvg();
                },
                child: CustomPaint(
                  painter: _SignaturePainter(strokes: _strokes),
                  child: Container(
                    color: AppColors.inputBg,
                    child: _strokes.isEmpty
                        ? const Center(
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(Icons.touch_app, color: AppColors.textMuted, size: 28),
                                SizedBox(height: 6),
                                Text(
                                  'Sign with finger or stylus inside this box\n(उंगली या स्टाइलस से यहाँ साइन करें)',
                                  textAlign: TextAlign.center,
                                  style: TextStyle(
                                    color: AppColors.textMuted,
                                    fontSize: 12,
                                  ),
                                ),
                              ],
                            ),
                          )
                        : null,
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _SignaturePainter extends CustomPainter {
  final List<List<Offset>> strokes;

  _SignaturePainter({required this.strokes});

  @override
  void paint(Canvas canvas, Size size) {
    // Draw subtle guideline
    final guidePaint = Paint()
      ..color = AppColors.divider.withValues(alpha: 0.5)
      ..strokeWidth = 1
      ..style = PaintingStyle.stroke;
    canvas.drawLine(
      Offset(20, size.height - 35),
      Offset(size.width - 20, size.height - 35),
      guidePaint,
    );

    // Draw ink
    final paint = Paint()
      ..color = AppColors.gold
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round
      ..strokeWidth = 3.5
      ..style = PaintingStyle.stroke;

    for (final stroke in strokes) {
      if (stroke.length < 2) continue;
      final path = Path();
      path.moveTo(stroke[0].dx, stroke[0].dy);
      for (int i = 1; i < stroke.length; i++) {
        path.lineTo(stroke[i].dx, stroke[i].dy);
      }
      canvas.drawPath(path, paint);
    }
  }

  @override
  bool shouldRepaint(covariant _SignaturePainter oldDelegate) => true;
}
