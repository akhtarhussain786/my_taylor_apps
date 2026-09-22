import 'package:flutter/material.dart';
import '../../constants/colors.dart';
import '../../models/order_model.dart';
import '../../providers/app_state.dart';
import '../../widgets/signature_pad_widget.dart';

class DeliveryProofScreen extends StatefulWidget {
  final OrderModel order;
  final AppState appState;

  const DeliveryProofScreen({
    super.key,
    required this.order,
    required this.appState,
  });

  @override
  State<DeliveryProofScreen> createState() => _DeliveryProofScreenState();
}

class _DeliveryProofScreenState extends State<DeliveryProofScreen> {
  late TextEditingController _nameController;
  String _relation = 'Self';
  String _signatureData = '';
  bool _codCollected = false;
  bool _isSubmitting = false;

  final List<String> _relations = [
    'Self (स्वयं)',
    'Spouse (पति/पत्नी)',
    'Family Member (परिवार)',
    'Guard / Reception (सुरक्षाकर्मी)',
    'Neighbor (पड़ोसी)',
  ];

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(text: widget.order.customerName);
    _codCollected = !widget.order.isCod;
  }

  @override
  void dispose() {
    _nameController.dispose();
    super.dispose();
  }

  void _submitDelivery() async {
    final name = _nameController.text.trim();
    if (name.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please enter recipient name')),
      );
      return;
    }

    if (_signatureData.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Customer signature is required. Please sign in the box.'),
          backgroundColor: AppColors.rose,
        ),
      );
      return;
    }

    if (widget.order.isCod && !_codCollected) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please confirm cash collection before completing COD delivery.'),
          backgroundColor: AppColors.amber,
        ),
      );
      return;
    }

    setState(() => _isSubmitting = true);

    final result = await widget.appState.completeDelivery(
      orderId: widget.order.id,
      recipientName: name,
      recipientRelation: _relation.split('(').first.trim(),
      signatureData: _signatureData,
      latitude: widget.order.latitude,
      longitude: widget.order.longitude,
    );

    setState(() => _isSubmitting = false);

    if (result['success'] == true && mounted) {
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (ctx) => AlertDialog(
          backgroundColor: AppColors.surface,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                padding: const EdgeInsets.all(16),
                decoration: const BoxDecoration(
                  color: AppColors.emerald,
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.verified, color: Colors.white, size: 38),
              ),
              const SizedBox(height: 16),
              const Text(
                'Parcel Delivered Successfully!',
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: AppColors.textPrimary,
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Order: ${widget.order.bookingId}\nHanded to: $name (${_relation.split('(').first.trim()})',
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: AppColors.gold,
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 12),
              const Text(
                'Digital signature & delivery timestamp have been logged to the central dashboard.',
                textAlign: TextAlign.center,
                style: TextStyle(color: AppColors.textSecondary, fontSize: 12),
              ),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: () {
                  Navigator.pop(ctx); // close dialog
                  Navigator.pop(context); // close proof screen
                  Navigator.pop(context); // close detail screen
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.emerald,
                  foregroundColor: Colors.white,
                  minimumSize: const Size(double.infinity, 44),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                child: const Text('Back to Fleet Queue', style: TextStyle(fontWeight: FontWeight.bold)),
              ),
            ],
          ),
        ),
      );
    } else if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message'] ?? 'Failed to complete delivery'),
          backgroundColor: AppColors.rose,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Proof of Delivery (POD)',
              style: TextStyle(color: AppColors.textPrimary, fontSize: 16, fontWeight: FontWeight.bold),
            ),
            Text(
              'Order: ${widget.order.bookingId}',
              style: const TextStyle(color: AppColors.gold, fontSize: 11),
            ),
          ],
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Recipient Details Box
            const Text(
              '1. Recipient Information / प्राप्तकर्ता की जानकारी',
              style: TextStyle(
                color: AppColors.goldLight,
                fontWeight: FontWeight.bold,
                fontSize: 14,
              ),
            ),
            const SizedBox(height: 10),

            Container(
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: AppColors.cardBorder),
              ),
              child: TextField(
                controller: _nameController,
                style: const TextStyle(color: AppColors.textPrimary, fontWeight: FontWeight.bold),
                decoration: const InputDecoration(
                  prefixIcon: Icon(Icons.badge, color: AppColors.gold),
                  labelText: 'Recipient Name (प्राप्तकर्ता का नाम)',
                  labelStyle: TextStyle(color: AppColors.textSecondary, fontSize: 13),
                  border: InputBorder.none,
                  contentPadding: EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                ),
              ),
            ),
            const SizedBox(height: 14),

            // Relationship Chips
            const Text(
              '2. Relationship to Customer / संबंध',
              style: TextStyle(
                color: AppColors.goldLight,
                fontWeight: FontWeight.bold,
                fontSize: 14,
              ),
            ),
            const SizedBox(height: 10),

            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: _relations.map((rel) {
                final isSelected = _relation == rel;
                return ChoiceChip(
                  label: Text(rel),
                  selected: isSelected,
                  selectedColor: AppColors.gold,
                  backgroundColor: AppColors.surface,
                  labelStyle: TextStyle(
                    color: isSelected ? const Color(0xFF0F172A) : AppColors.textSecondary,
                    fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
                    fontSize: 12,
                  ),
                  onSelected: (_) => setState(() => _relation = rel),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                    side: BorderSide(color: isSelected ? AppColors.gold : AppColors.cardBorder),
                  ),
                );
              }).toList(),
            ),
            const SizedBox(height: 20),

            // COD Confirmation if applicable
            if (widget.order.isCod) ...[
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: AppColors.amber.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: AppColors.amber),
                ),
                child: Row(
                  children: [
                    Checkbox(
                      value: _codCollected,
                      activeColor: AppColors.amber,
                      checkColor: Colors.black,
                      onChanged: (val) => setState(() => _codCollected = val ?? false),
                    ),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Collected ₹${widget.order.totalAmount.toInt()} via Cash / UPI QR',
                            style: const TextStyle(
                              color: AppColors.amber,
                              fontWeight: FontWeight.bold,
                              fontSize: 13,
                            ),
                          ),
                          const Text(
                            'Check this box once payment is received',
                            style: TextStyle(color: AppColors.textMuted, fontSize: 11),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),
            ],

            // Digital Signature Pad
            const Text(
              '3. Customer Signature / ग्राहक हस्ताक्षर',
              style: TextStyle(
                color: AppColors.goldLight,
                fontWeight: FontWeight.bold,
                fontSize: 14,
              ),
            ),
            const SizedBox(height: 10),

            SignaturePadWidget(
              onSignatureChanged: (svg) {
                _signatureData = svg;
              },
            ),
            const SizedBox(height: 28),

            // Submit Button
            ElevatedButton(
              onPressed: _isSubmitting ? null : _submitDelivery,
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.emerald,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 16),
                minimumSize: const Size(double.infinity, 54),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                elevation: 4,
              ),
              child: _isSubmitting
                  ? const SizedBox(
                      width: 24,
                      height: 24,
                      child: CircularProgressIndicator(
                        strokeWidth: 2.5,
                        valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                      ),
                    )
                  : const Text(
                      'CONFIRM & COMPLETE DELIVERY',
                      style: TextStyle(
                        fontWeight: FontWeight.w900,
                        fontSize: 14,
                        letterSpacing: 1,
                      ),
                    ),
            ),
            const SizedBox(height: 40),
          ],
        ),
      ),
    );
  }
}
