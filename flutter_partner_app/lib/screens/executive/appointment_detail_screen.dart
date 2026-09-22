import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../constants/colors.dart';
import '../../models/appointment_model.dart';
import '../../providers/app_state.dart';
import '../../widgets/status_badge.dart';
import 'measurement_form_screen.dart';

class AppointmentDetailScreen extends StatefulWidget {
  final AppointmentModel appointment;
  final AppState appState;

  const AppointmentDetailScreen({
    super.key,
    required this.appointment,
    required this.appState,
  });

  @override
  State<AppointmentDetailScreen> createState() => _AppointmentDetailScreenState();
}

class _AppointmentDetailScreenState extends State<AppointmentDetailScreen> {
  late AppointmentModel _apt;
  bool _isUpdating = false;

  @override
  void initState() {
    super.initState();
    _apt = widget.appointment;
  }

  void _callCustomer() async {
    final uri = Uri.parse('tel:${_apt.customerMobile}');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  void _whatsappCustomer() async {
    final cleanMobile = _apt.customerMobile.replaceAll(RegExp(r'[^0-9]'), '');
    final uri = Uri.parse('https://wa.me/91$cleanMobile?text=Hello%20${_apt.customerName},%20I%20am%20your%20MY%20TAYLOR%20Measurement%20Master%20for%20appointment%20${_apt.appointmentCode}.');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  void _openGoogleMaps() async {
    final query = Uri.encodeComponent('${_apt.houseNo}, ${_apt.street}, ${_apt.area}, ${_apt.city} ${_apt.pincode}');
    final uri = Uri.parse('https://www.google.com/maps/search/?api=1&query=$query');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  Future<void> _updateStatus(String newStatus) async {
    setState(() => _isUpdating = true);
    final success = await widget.appState.updateAppointmentStatus(_apt.id, newStatus);
    if (success && mounted) {
      setState(() {
        _apt = _apt.copyWith(status: newStatus);
        _isUpdating = false;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Status updated: ${newStatus.replaceAll('_', ' ')}'),
          backgroundColor: AppColors.emerald,
        ),
      );
    } else {
      setState(() => _isUpdating = false);
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
            Text(
              _apt.appointmentCode,
              style: const TextStyle(
                color: AppColors.gold,
                fontWeight: FontWeight.bold,
                fontSize: 16,
              ),
            ),
            const Text(
              'Doorstep Measurement Workflow',
              style: TextStyle(color: AppColors.textSecondary, fontSize: 11),
            ),
          ],
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Status & Express Banner
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.cardBorder),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Appointment Status',
                          style: TextStyle(color: AppColors.textMuted, fontSize: 12),
                        ),
                        const SizedBox(height: 4),
                        StatusBadge(
                          status: _apt.status,
                          isExpress: _apt.is24HourExpress,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    decoration: BoxDecoration(
                      color: AppColors.inputBg,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(
                          _apt.appointmentDate,
                          style: const TextStyle(
                            color: AppColors.textPrimary,
                            fontWeight: FontWeight.bold,
                            fontSize: 13,
                          ),
                        ),
                        Text(
                          _apt.timeSlot,
                          style: const TextStyle(
                            color: AppColors.gold,
                            fontSize: 11,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Step Workflow Progression
            _buildWorkflowSteps(),
            const SizedBox(height: 20),

            // Customer Info Box
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.cardBorder),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Row(
                    children: [
                      Icon(Icons.person_pin, color: AppColors.gold, size: 20),
                      SizedBox(width: 8),
                      Text(
                        'Customer & Location',
                        style: TextStyle(
                          color: AppColors.textPrimary,
                          fontWeight: FontWeight.bold,
                          fontSize: 15,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Text(
                    _apt.customerName,
                    style: const TextStyle(
                      color: AppColors.textPrimary,
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    _apt.customerMobile,
                    style: const TextStyle(
                      color: AppColors.textSecondary,
                      fontSize: 14,
                    ),
                  ),
                  const SizedBox(height: 12),
                  const Divider(color: AppColors.divider),
                  const SizedBox(height: 8),
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Icon(Icons.location_on, color: AppColors.rose, size: 18),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              _apt.fullAddress,
                              style: const TextStyle(
                                color: AppColors.textPrimary,
                                fontSize: 13,
                                height: 1.4,
                              ),
                            ),
                            if (_apt.landmark != null && _apt.landmark!.isNotEmpty) ...[
                              const SizedBox(height: 4),
                              Text(
                                'Landmark: ${_apt.landmark}',
                                style: const TextStyle(
                                  color: AppColors.amber,
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),

                  // Quick Action Buttons (Call, WhatsApp, Maps)
                  Row(
                    children: [
                      Expanded(
                        child: ElevatedButton.icon(
                          onPressed: _callCustomer,
                          icon: const Icon(Icons.phone, size: 16),
                          label: const Text('Call'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.surfaceLight,
                            foregroundColor: AppColors.textPrimary,
                            padding: const EdgeInsets.symmetric(vertical: 10),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: ElevatedButton.icon(
                          onPressed: _whatsappCustomer,
                          icon: const Icon(Icons.chat, size: 16),
                          label: const Text('WhatsApp'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFF25D366),
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 10),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: ElevatedButton.icon(
                          onPressed: _openGoogleMaps,
                          icon: const Icon(Icons.navigation, size: 16),
                          label: const Text('Map'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.royalBlue,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 10),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Garment & Order Summary
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.cardBorder),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Tailoring Service Requested',
                    style: TextStyle(
                      color: AppColors.textPrimary,
                      fontWeight: FontWeight.bold,
                      fontSize: 15,
                    ),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        _apt.serviceName,
                        style: const TextStyle(
                          color: AppColors.goldLight,
                          fontWeight: FontWeight.bold,
                          fontSize: 14,
                        ),
                      ),
                      Text(
                        '₹${_apt.basePrice.toInt()}',
                        style: const TextStyle(
                          color: AppColors.textPrimary,
                          fontWeight: FontWeight.bold,
                          fontSize: 14,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'Doorstep Measurement Fee',
                        style: TextStyle(color: AppColors.textSecondary, fontSize: 13),
                      ),
                      Text(
                        '₹${_apt.measurementFee.toInt()}',
                        style: const TextStyle(color: AppColors.textSecondary, fontSize: 13),
                      ),
                    ],
                  ),
                  if (_apt.is24HourExpress) ...[
                    const SizedBox(height: 6),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          '24-Hour Express Guarantee',
                          style: TextStyle(color: AppColors.rose, fontSize: 13, fontWeight: FontWeight.bold),
                        ),
                        Text(
                          '₹${_apt.expressPrice.toInt()}',
                          style: const TextStyle(color: AppColors.rose, fontSize: 13, fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
                  ],
                  if (_apt.notes != null && _apt.notes!.isNotEmpty) ...[
                    const SizedBox(height: 12),
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: AppColors.inputBg,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        'Customer Notes: ${_apt.notes}',
                        style: const TextStyle(
                          color: AppColors.textSecondary,
                          fontSize: 12,
                          fontStyle: FontStyle.italic,
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Take Measurement Button
            ElevatedButton.icon(
              onPressed: _apt.isCompleted
                  ? null
                  : () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => MeasurementFormScreen(
                            appointment: _apt,
                            appState: widget.appState,
                          ),
                        ),
                      );
                    },
              icon: Icon(
                _apt.isCompleted ? Icons.check_circle : Icons.straighten,
                color: const Color(0xFF0F172A),
              ),
              label: Text(
                _apt.isCompleted ? 'FITTING COMPLETED' : 'OPEN DIGITAL MEASUREMENT FORM',
                style: const TextStyle(
                  color: Color(0xFF0F172A),
                  fontWeight: FontWeight.w800,
                  fontSize: 14,
                  letterSpacing: 0.8,
                ),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.gold,
                padding: const EdgeInsets.symmetric(vertical: 16),
                minimumSize: const Size(double.infinity, 52),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                elevation: 4,
              ),
            ),
            const SizedBox(height: 40),
          ],
        ),
      ),
    );
  }

  Widget _buildWorkflowSteps() {
    final status = _apt.status;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.cardBorder),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Live Field Step Progression',
            style: TextStyle(
              color: AppColors.textPrimary,
              fontWeight: FontWeight.bold,
              fontSize: 14,
            ),
          ),
          const SizedBox(height: 16),

          // Step 1: Assigned
          _buildStepRow(
            stepNum: 1,
            title: 'Assigned to Master',
            subtitle: 'Booking verified in system',
            isDone: true,
            isActive: false,
          ),

          // Step 2: On The Way
          _buildStepRow(
            stepNum: 2,
            title: 'On The Way (रास्ते में)',
            subtitle: 'Master is travelling to customer doorstep',
            isDone: status == 'EXECUTIVE_ON_THE_WAY' || status == 'EXECUTIVE_ARRIVED' || status == 'MEASUREMENT_STARTED' || status == 'MEASUREMENT_COMPLETED',
            isActive: status == 'BOOKED' || status == 'EXECUTIVE_ASSIGNED',
            actionButton: status == 'BOOKED' || status == 'EXECUTIVE_ASSIGNED'
                ? ElevatedButton(
                    onPressed: _isUpdating ? null : () => _updateStatus('EXECUTIVE_ON_THE_WAY'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.purple,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                      textStyle: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
                    ),
                    child: const Text('Start Route'),
                  )
                : null,
          ),

          // Step 3: Arrived
          _buildStepRow(
            stepNum: 3,
            title: 'Arrived at Doorstep (पहुँच गए)',
            subtitle: 'Master reached location',
            isDone: status == 'EXECUTIVE_ARRIVED' || status == 'MEASUREMENT_STARTED' || status == 'MEASUREMENT_COMPLETED',
            isActive: status == 'EXECUTIVE_ON_THE_WAY',
            actionButton: status == 'EXECUTIVE_ON_THE_WAY'
                ? ElevatedButton(
                    onPressed: _isUpdating ? null : () => _updateStatus('EXECUTIVE_ARRIVED'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.amber,
                      foregroundColor: Colors.black,
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                      textStyle: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
                    ),
                    child: const Text('Mark Arrived'),
                  )
                : null,
          ),

          // Step 4: Completed
          _buildStepRow(
            stepNum: 4,
            title: 'Digital Fitting & Submit',
            subtitle: 'Take body measurements & create 24H SLA order',
            isDone: status == 'MEASUREMENT_COMPLETED',
            isActive: status == 'EXECUTIVE_ARRIVED' || status == 'MEASUREMENT_STARTED',
            isLast: true,
          ),
        ],
      ),
    );
  }

  Widget _buildStepRow({
    required int stepNum,
    required String title,
    required String subtitle,
    required bool isDone,
    required bool isActive,
    Widget? actionButton,
    bool isLast = false,
  }) {
    Color iconBg = AppColors.surfaceLight;
    Color iconFg = AppColors.textMuted;
    if (isDone) {
      iconBg = AppColors.emerald;
      iconFg = Colors.white;
    } else if (isActive) {
      iconBg = AppColors.gold;
      iconFg = const Color(0xFF0F172A);
    }

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Column(
          children: [
            Container(
              width: 28,
              height: 28,
              decoration: BoxDecoration(
                color: iconBg,
                shape: BoxShape.circle,
              ),
              child: Center(
                child: isDone
                    ? const Icon(Icons.check, size: 16, color: Colors.white)
                    : Text(
                        '$stepNum',
                        style: TextStyle(
                          color: iconFg,
                          fontWeight: FontWeight.bold,
                          fontSize: 12,
                        ),
                      ),
              ),
            ),
            if (!isLast)
              Container(
                width: 2,
                height: 38,
                color: isDone ? AppColors.emerald : AppColors.divider,
              ),
          ],
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: TextStyle(
                  color: isDone || isActive ? AppColors.textPrimary : AppColors.textMuted,
                  fontWeight: FontWeight.bold,
                  fontSize: 13,
                ),
              ),
              Text(
                subtitle,
                style: const TextStyle(
                  color: AppColors.textSecondary,
                  fontSize: 11,
                ),
              ),
              const SizedBox(height: 6),
            ],
          ),
        ),
        ?actionButton,
      ],
    );
  }
}
