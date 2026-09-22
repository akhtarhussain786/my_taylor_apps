import 'package:flutter/material.dart';
import '../../constants/colors.dart';
import '../../constants/garment_templates.dart';
import '../../models/appointment_model.dart';
import '../../providers/app_state.dart';
import '../../widgets/garment_measure_field.dart';

class MeasurementFormScreen extends StatefulWidget {
  final AppointmentModel appointment;
  final AppState appState;

  const MeasurementFormScreen({
    super.key,
    required this.appointment,
    required this.appState,
  });

  @override
  State<MeasurementFormScreen> createState() => _MeasurementFormScreenState();
}

class _MeasurementFormScreenState extends State<MeasurementFormScreen> {
  late GarmentTemplate _currentTemplate;
  final Map<String, double> _measurements = {};
  final Map<String, String> _selectedStyles = {};
  String _fitPreference = 'Regular Fit';
  String _fabricSource = 'MY_TAYLOR_FABRIC';
  int _selectedFabricId = 1;
  final TextEditingController _notesController = TextEditingController();
  bool _isSubmitting = false;

  final List<String> _fitOptions = ['Slim Fit', 'Regular Fit', 'Comfort Fit', 'Loose Fit'];

  @override
  void initState() {
    super.initState();
    _currentTemplate = GarmentTemplates.getByKey(widget.appointment.serviceName);
    _initDefaultValues();
  }

  void _initDefaultValues() {
    _measurements.clear();
    for (final field in _currentTemplate.fields) {
      final cleanKey = _cleanKey(field);
      // Sensible standard default sizes
      if (cleanKey.contains('chest') || cleanKey.contains('bust')) {
        _measurements[cleanKey] = 40.0;
      } else if (cleanKey.contains('waist')) {
        _measurements[cleanKey] = 34.0;
      } else if (cleanKey.contains('neck')) {
        _measurements[cleanKey] = 16.0;
      } else if (cleanKey.contains('shoulder')) {
        _measurements[cleanKey] = 18.0;
      } else if (cleanKey.contains('sleeve')) {
        _measurements[cleanKey] = 25.0;
      } else if (cleanKey.contains('length')) {
        _measurements[cleanKey] = 30.0;
      } else if (cleanKey.contains('inseam')) {
        _measurements[cleanKey] = 32.0;
      } else if (cleanKey.contains('hip')) {
        _measurements[cleanKey] = 41.0;
      } else {
        _measurements[cleanKey] = 15.0;
      }
    }

    _selectedStyles.clear();
    _currentTemplate.styleOptions.forEach((k, v) {
      if (v.isNotEmpty) _selectedStyles[k] = v.first;
    });
  }

  String _cleanKey(String field) {
    return field.split('(').first.trim().toLowerCase().replaceAll(' ', '_');
  }

  void _changeGarmentTemplate(GarmentTemplate newTemplate) {
    setState(() {
      _currentTemplate = newTemplate;
      _initDefaultValues();
    });
  }

  void _submitForm() async {
    setState(() => _isSubmitting = true);

    final result = await widget.appState.submitMeasurement(
      appointmentId: widget.appointment.id,
      garmentType: _currentTemplate.name,
      fitPreference: _fitPreference,
      notes: _notesController.text.trim(),
      fabricSource: _fabricSource,
      fabricId: _selectedFabricId,
      measurements: _measurements,
      designSpecs: _selectedStyles,
    );

    setState(() => _isSubmitting = false);

    if (result['success'] == true && mounted) {
      final data = result['data'] ?? {};
      final bookingId = data['booking_id'] ?? 'MYT-ORDER';
      final mCode = data['measurement_code'] ?? 'MT-M-001';

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
                child: const Icon(Icons.check, color: Colors.white, size: 36),
              ),
              const SizedBox(height: 16),
              const Text(
                'Fitting Recorded & Order Dispatched!',
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: AppColors.textPrimary,
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Measurement Profile: $mCode\n24-Hour Production Order: $bookingId',
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: AppColors.gold,
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 12),
              const Text(
                'The cutting & master tailor queue has been alerted with strict 24H SLA countdown.',
                textAlign: TextAlign.center,
                style: TextStyle(color: AppColors.textSecondary, fontSize: 12),
              ),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: () {
                  Navigator.pop(ctx); // close dialog
                  Navigator.pop(context); // close form
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.gold,
                  foregroundColor: const Color(0xFF0F172A),
                  minimumSize: const Size(double.infinity, 44),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                child: const Text('Back to Schedule', style: TextStyle(fontWeight: FontWeight.bold)),
              ),
            ],
          ),
        ),
      );
    } else if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message'] ?? 'Failed to submit measurement'),
          backgroundColor: AppColors.rose,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final fabrics = widget.appState.fabrics;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Fitting for ${widget.appointment.customerName}',
              style: const TextStyle(
                color: AppColors.textPrimary,
                fontWeight: FontWeight.bold,
                fontSize: 15,
              ),
            ),
            Text(
              'Apt: ${widget.appointment.appointmentCode}',
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
            // Garment Category Selector Chips
            const Text(
              '1. Select Garment Template / कपड़ा प्रकार',
              style: TextStyle(
                color: AppColors.goldLight,
                fontWeight: FontWeight.bold,
                fontSize: 14,
              ),
            ),
            const SizedBox(height: 10),
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: GarmentTemplates.all.map((tmpl) {
                  final isSelected = tmpl.key == _currentTemplate.key;
                  return Padding(
                    padding: const EdgeInsets.only(right: 8.0),
                    child: ChoiceChip(
                      label: Text('${tmpl.icon} ${tmpl.hindiName} (${tmpl.name.split(' ').last})'),
                      selected: isSelected,
                      selectedColor: AppColors.gold,
                      backgroundColor: AppColors.surface,
                      labelStyle: TextStyle(
                        color: isSelected ? const Color(0xFF0F172A) : AppColors.textSecondary,
                        fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
                        fontSize: 12,
                      ),
                      onSelected: (_) => _changeGarmentTemplate(tmpl),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                        side: BorderSide(color: isSelected ? AppColors.gold : AppColors.cardBorder),
                      ),
                    ),
                  );
                }).toList(),
              ),
            ),
            const SizedBox(height: 20),

            // Fit Preference Selector
            const Text(
              '2. Fit Preference / फिटिंग का चुनाव',
              style: TextStyle(
                color: AppColors.goldLight,
                fontWeight: FontWeight.bold,
                fontSize: 14,
              ),
            ),
            const SizedBox(height: 10),
            Row(
              children: _fitOptions.map((fit) {
                final isSelected = _fitPreference == fit;
                return Expanded(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 3.0),
                    child: InkWell(
                      onTap: () => setState(() => _fitPreference = fit),
                      borderRadius: BorderRadius.circular(10),
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 10),
                        decoration: BoxDecoration(
                          color: isSelected ? AppColors.gold.withValues(alpha: 0.2) : AppColors.surface,
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(
                            color: isSelected ? AppColors.gold : AppColors.cardBorder,
                            width: isSelected ? 1.5 : 1,
                          ),
                        ),
                        alignment: Alignment.center,
                        child: Text(
                          fit,
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            color: isSelected ? AppColors.gold : AppColors.textSecondary,
                            fontSize: 11,
                            fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                          ),
                        ),
                      ),
                    ),
                  ),
                );
              }).toList(),
            ),
            const SizedBox(height: 20),

            // Precision Body Measurements Form
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  '3. Precision Body Measurements (Inches)',
                  style: TextStyle(
                    color: AppColors.goldLight,
                    fontWeight: FontWeight.bold,
                    fontSize: 14,
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(
                    color: AppColors.surfaceLight,
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: const Text(
                    '+/- 0.5" Steppers',
                    style: TextStyle(color: AppColors.textMuted, fontSize: 10),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),

            ..._currentTemplate.fields.map((field) {
              final key = _cleanKey(field);
              final val = _measurements[key] ?? 30.0;
              return GarmentMeasureField(
                label: field,
                initialValue: val,
                onChanged: (newVal) {
                  _measurements[key] = newVal;
                },
              );
            }),
            const SizedBox(height: 20),

            // Style Customizations (Collars, Cuffs, Pockets, Plackets)
            const Text(
              '4. Style Details & Customizations',
              style: TextStyle(
                color: AppColors.goldLight,
                fontWeight: FontWeight.bold,
                fontSize: 14,
              ),
            ),
            const SizedBox(height: 12),

            ..._currentTemplate.styleOptions.entries.map((entry) {
              return Container(
                margin: const EdgeInsets.only(bottom: 10),
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppColors.cardBorder),
                ),
                child: Row(
                  children: [
                    Expanded(
                      flex: 2,
                      child: Text(
                        entry.key,
                        style: const TextStyle(
                          color: AppColors.textPrimary,
                          fontWeight: FontWeight.w600,
                          fontSize: 13,
                        ),
                      ),
                    ),
                    Expanded(
                      flex: 3,
                      child: DropdownButtonHideUnderline(
                        child: DropdownButton<String>(
                          value: _selectedStyles[entry.key] ?? entry.value.first,
                          dropdownColor: AppColors.surface,
                          icon: const Icon(Icons.arrow_drop_down, color: AppColors.gold),
                          isExpanded: true,
                          style: const TextStyle(color: AppColors.goldLight, fontSize: 13),
                          items: entry.value.map((opt) {
                            return DropdownMenuItem<String>(
                              value: opt,
                              child: Text(opt, overflow: TextOverflow.ellipsis),
                            );
                          }).toList(),
                          onChanged: (newOpt) {
                            if (newOpt != null) {
                              setState(() => _selectedStyles[entry.key] = newOpt);
                            }
                          },
                        ),
                      ),
                    ),
                  ],
                ),
              );
            }),
            const SizedBox(height: 20),

            // Fabric Source Selection
            const Text(
              '5. Fabric Selection / कपड़ा चुनाव',
              style: TextStyle(
                color: AppColors.goldLight,
                fontWeight: FontWeight.bold,
                fontSize: 14,
              ),
            ),
            const SizedBox(height: 10),

            Row(
              children: [
                Expanded(
                  child: InkWell(
                    onTap: () => setState(() => _fabricSource = 'MY_TAYLOR_FABRIC'),
                    child: Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: _fabricSource == 'MY_TAYLOR_FABRIC'
                            ? AppColors.gold.withValues(alpha: 0.15)
                            : AppColors.surface,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: _fabricSource == 'MY_TAYLOR_FABRIC' ? AppColors.gold : AppColors.cardBorder,
                          width: _fabricSource == 'MY_TAYLOR_FABRIC' ? 1.5 : 1,
                        ),
                      ),
                      child: const Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'MY TAYLOR Fabric',
                            style: TextStyle(color: AppColors.textPrimary, fontWeight: FontWeight.bold, fontSize: 13),
                          ),
                          SizedBox(height: 2),
                          Text(
                            'Premium Giza, Linen & Silks',
                            style: TextStyle(color: AppColors.textMuted, fontSize: 11),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: InkWell(
                    onTap: () => setState(() => _fabricSource = 'CUSTOMER_PROVIDED'),
                    child: Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: _fabricSource == 'CUSTOMER_PROVIDED'
                            ? AppColors.gold.withValues(alpha: 0.15)
                            : AppColors.surface,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: _fabricSource == 'CUSTOMER_PROVIDED' ? AppColors.gold : AppColors.cardBorder,
                          width: _fabricSource == 'CUSTOMER_PROVIDED' ? 1.5 : 1,
                        ),
                      ),
                      child: const Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Customer Own Fabric',
                            style: TextStyle(color: AppColors.textPrimary, fontWeight: FontWeight.bold, fontSize: 13),
                          ),
                          SizedBox(height: 2),
                          Text(
                            'Collected during fitting',
                            style: TextStyle(color: AppColors.textMuted, fontSize: 11),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ),

            if (_fabricSource == 'MY_TAYLOR_FABRIC') ...[
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppColors.cardBorder),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Choose Fabric Swatch:',
                      style: TextStyle(color: AppColors.textSecondary, fontSize: 12),
                    ),
                    const SizedBox(height: 8),
                    ...fabrics.map((fab) {
                      final isSelected = _selectedFabricId == fab['id'];
                      return InkWell(
                        onTap: () => setState(() => _selectedFabricId = fab['id'] as int),
                        child: Container(
                          margin: const EdgeInsets.only(bottom: 6),
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: isSelected ? AppColors.surfaceLight : AppColors.inputBg,
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(
                              color: isSelected ? AppColors.gold : Colors.transparent,
                            ),
                          ),
                          child: Row(
                            children: [
                              Icon(
                                isSelected ? Icons.radio_button_checked : Icons.radio_button_off,
                                color: isSelected ? AppColors.gold : AppColors.textMuted,
                                size: 16,
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  '${fab['name']} (${fab['color']})',
                                  style: TextStyle(
                                    color: isSelected ? AppColors.goldLight : AppColors.textPrimary,
                                    fontSize: 12,
                                    fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                                  ),
                                ),
                              ),
                              Text(
                                '₹${fab['price_per_meter']}/m',
                                style: const TextStyle(color: AppColors.emerald, fontSize: 11, fontWeight: FontWeight.bold),
                              ),
                            ],
                          ),
                        ),
                      );
                    }),
                  ],
                ),
              ),
            ],
            const SizedBox(height: 20),

            // Master Notes
            const Text(
              '6. Master Tailor Notes / विशेष निर्देश',
              style: TextStyle(
                color: AppColors.goldLight,
                fontWeight: FontWeight.bold,
                fontSize: 14,
              ),
            ),
            const SizedBox(height: 8),
            Container(
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: AppColors.cardBorder),
              ),
              child: TextField(
                controller: _notesController,
                maxLines: 3,
                style: const TextStyle(color: AppColors.textPrimary, fontSize: 13),
                decoration: const InputDecoration(
                  hintText: 'e.g. Extra ease around wrist for heavy watch, contrast inner piping...',
                  hintStyle: TextStyle(color: AppColors.textMuted, fontSize: 12),
                  border: InputBorder.none,
                  contentPadding: EdgeInsets.all(12),
                ),
              ),
            ),
            const SizedBox(height: 28),

            // Submit Button
            ElevatedButton(
              onPressed: _isSubmitting ? null : _submitForm,
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.gold,
                foregroundColor: const Color(0xFF0F172A),
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
                        valueColor: AlwaysStoppedAnimation<Color>(Color(0xFF0F172A)),
                      ),
                    )
                  : const Text(
                      'SAVE FITTING & LAUNCH 24H ORDER',
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
