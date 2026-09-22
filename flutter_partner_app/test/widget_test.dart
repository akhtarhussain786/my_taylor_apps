import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_partner_app/main.dart';

void main() {
  testWidgets('MyTaylorPartnerApp builds properly', (WidgetTester tester) async {
    await tester.pumpWidget(const MyTaylorPartnerApp());
    expect(find.byType(MyTaylorPartnerApp), findsOneWidget);
  });
}
